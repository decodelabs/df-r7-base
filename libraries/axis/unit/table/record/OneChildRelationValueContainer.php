<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\record;

use df;
use df\core;
use df\axis;
use df\opal;

class OneChildRelationValueContainer implements 
    opal\record\ITaskAwareValueContainer, 
    opal\record\IPreparedValueContainer,
    opal\record\IIdProviderValueContainer {
    
    protected $_insertPrimaryKeySet;
    protected $_record = false;
    protected $_field;

    public function __construct(axis\schema\IOneChildField $field) {
        $this->_field = $field;
    }

    public function getTargetUnitId() {
        return $this->_field->getTargetUnitId();
    }

    public function getTargetUnit($clusterId=null) {
        if($this->_record) {
            $localUnit = $this->_record->getRecordAdapter();

            if($clusterId === null && !$this->_field->isOnGlobalCluster()) {
                $clusterId = $localUnit->getClusterId();
            }
        }
        
        return $this->_getTargetUnit($clusterId);
    }

    protected function _getTargetUnit($clusterId=null) {
        return axis\Model::loadUnitFromId($this->_field->getTargetUnitId(), $clusterId);
    }
    
    public function isPrepared() {
        return $this->_record !== false;
    }
    
    public function prepareValue(opal\record\IRecord $record, $fieldName) {
        $localUnit = $record->getRecordAdapter();
        $clusterId = $this->_field->isOnGlobalCluster() ? null : $localUnit->getClusterId();
        $targetUnit = $this->_getTargetUnit($clusterId);
        $query = $targetUnit->fetch();
        
        if($this->_insertPrimaryKeySet) {
            foreach($this->_insertPrimaryKeySet->toArray() as $field => $value) {
                $query->where($field, '=', $value);
            }
        } else {
            $query->where($this->_field->getTargetField(), '=', $record->getPrimaryKeySet());
        }
        
        $this->_record = $query->toRow();
        
        if($this->_record) {
            $inverseValue = $this->_record->getRaw($this->_field->getTargetField());
            $inverseValue->populateInverse($record);
        }
        
        return $this;
    }
    
    public function prepareToSetValue(opal\record\IRecord $record, $fieldName) {
        return $this;
    }
    
    public function eq($value) {
        if(!$this->_record) {
            return false;
        }
        
        if($value instanceof self
        || $value instanceof opal\record\IRecord) {
            $value = $value->getPrimaryKeySet();
        } else if(!$value instanceof opal\record\IPrimaryKeySet) {
            return false;
        }
        
        return $this->_record->getPrimaryKeySet()->eq($value);
    }
    
    public function setValue($value) {
        $record = false;
        
        if($value instanceof self) {
            $record = $value->_record;
            $value = $value->getPrimaryKeySet();
        } else if($value instanceof opal\record\IPrimaryKeySetProvider) {
            $record = $value;
            $value = $value->getPrimaryKeySet();
        } else if(!$value instanceof opal\record\IPrimaryKeySet) {
            // TODO: swap array('id') for target primary fields
            $value = new opal\record\PrimaryKeySet(['id'], [$value]);
        }
        
        $this->_insertPrimaryKeySet = $value;
        $this->_record = $record;
        
        return $this;
    }
    
    public function getValue($default=null) {
        if($this->_record !== false) {
            return $this->_record;
        }
        
        return $default;
    }
    
    public function hasValue() {
        return $this->_record !== false && $this->_record !== null;
    }

    public function getStringValue($default='') {
        return $this->__toString();
    }

    public function getValueForStorage() {
        return null;
    }

    public function getRawId() {
        if($this->_record) {
            return $this->_record->getFirstKeyValue();
        }

        return null;
    }
    
    public function duplicateForChangeList() {
        $output = new self($this->_field);
        $output->_insertPrimaryKeySet = $this->_insertPrimaryKeySet;
        return $output;
    }
    
    public function populateInverse(opal\record\IRecord $record=null) {
        if(!$this->_insertPrimaryKeySet) {
            $this->_record = $record;
        }
        
        return $this;
    }
    
    
// Tasks
    public function deploySaveTasks(opal\record\task\ITaskSet $taskSet, opal\record\IRecord $record, $fieldName, opal\record\task\ITask $recordTask=null) {
        if($this->_insertPrimaryKeySet) {
            if(!$this->_record instanceof opal\record\IRecord) {
                $this->prepareValue($record, $fieldName);
            }
            
            $originalRecord = null;
            $targetField = $this->_field->getTargetField();

            if(!$record->isNew()) {
                $localUnit = $record->getRecordAdapter();
                $clusterId = $this->_field->isOnGlobalCluster() ? null : $localUnit->getClusterId();
                $targetUnit = axis\Model::loadUnitFromId($this->_field->getTargetUnitId(), $clusterId);
                
                $query = $targetUnit->fetch();
                        
                foreach($record->getPrimaryKeySet()->toArray() as $field => $value) {
                    $query->where($targetField.'_'.$field, '=', $value);
                }
                
                $originalRecord = $query->toRow();
            }
            
            if(!$this->_record) {
                $this->_insertPrimaryKeySet->updateWith(null);
            } else {
                $task = $this->_record->deploySaveTasks($taskSet);
                
                if($recordTask) {
                    $task->addDependency(
                        new opal\record\task\dependency\UpdateKeySetField(
                            $targetField, $recordTask
                        )
                    );
                } else if(!$this->_insertPrimaryKeySet->isNull()) {
                    $this->_record->set($targetField, $this->_insertPrimaryKeySet);
                }
            }
            
            if($originalRecord) {
                $originalRecord->set($targetField, null);
                $taskSet->save($originalRecord);
            }
        }
        
        return $this;
    }
    
    public function acceptSaveTaskChanges(opal\record\IRecord $record) {
        return $this;
    }
    
    public function deployDeleteTasks(opal\record\task\ITaskSet $taskSet, opal\record\IRecord $record, $fieldName, opal\record\task\ITask $recordTask=null) {
        core\stub($taskSet, $record, $recordTask);
    }
    
    public function acceptDeleteTaskChanges(opal\record\IRecord $record) {
        return $this;
    }
    
    
// Dump
    public function getDumpValue() {
        if($this->_record) {
            return $this->_record;
        }
        
        if($this->_insertPrimaryKeySet) {
            if($this->_insertPrimaryKeySet->countFields() == 1) {
                return $this->_insertPrimaryKeySet->getFirstKeyValue();
            }
            
            return $this->_insertPrimaryKeySet;
        }
        
        return '['.$this->_field->getTargetUnitId().']';
    }
    
    public function getDumpProperties() {
        if($this->_record) {
            return $this->_record;
        }
        
        $output = $this->_field->getTargetUnitId();
        
        if($this->_insertPrimaryKeySet) {
            $output .= ' : ';
            
            if($this->_insertPrimaryKeySet->countFields() == 1) {
                $value = $this->_insertPrimaryKeySet->getFirstKeyValue();
                
                if($value === null) {
                    $output .= 'null';
                } else {
                    $output .= $value;
                }
            } else {
                $t = [];
                
                foreach($this->_insertPrimaryKeySet->toArray() as $key => $value) {
                    $valString = $key.'=';
                    
                    if($value === null) {
                        $valString .= 'null';
                    } else {
                        $valString .= $value;
                    }
                    
                    $t[] = $valString;
                }
                
                $output .= implode(', ', $t);
            }
        } else {
            $output = '['.$output.']';
        }
        
        return $output;
    }
}
