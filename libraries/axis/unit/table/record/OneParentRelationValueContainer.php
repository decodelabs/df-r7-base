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

class OneParentRelationValueContainer implements opal\query\record\ITaskAwareValueContainer, opal\query\record\IPreparedValueContainer {
    
    protected $_targetField;
    protected $_record = false;
    protected $_insertPrimaryManifest;
    protected $_targetUnitId;
    
    public function __construct($targetUnitId, $targetField) {
        $this->_targetUnitId = $targetUnitId;
        $this->_targetField = $targetField;
    }
    
    public function isPrepared() {
        return $this->_record !== false;
    }
    
    public function prepareValue(opal\query\record\IRecord $record, $fieldName) {
        $application = $record->getRecordAdapter()->getApplication();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $query = $targetUnit->fetch();
        
        if($this->_insertPrimaryManifest) {
            foreach($this->_insertPrimaryManifest->toArray() as $field => $value) {
                $query->where($field, '=', $value);
            }
        } else {
            $query->where($this->_targetField, '=', $record->getPrimaryManifest());
            
            /*
            foreach($record->getPrimaryManifest()->toArray() as $field => $value) {
                $query->where($this->_targetField.'_'.$field, '=', $value);
            }
            */
        }
        
        $this->_record = $query->toRow();
        
        if($this->_record) {
            $inverseValue = $this->_record->getRaw($this->_targetField);
            $inverseValue->populateInverse($record);
        }
        
        return $this;
    }
    
    public function prepareToSetValue(opal\query\record\IRecord $record, $fieldName) {
        return $this;
    }
    
    public function eq($value) {
        if(!$this->_record) {
            return false;
        }
        
        if($value instanceof self
        || $value instanceof opal\query\record\IRecord) {
            $value = $value->getPrimaryManifest();
        } else if(!$value instanceof opal\query\record\IPrimaryManifest) {
            return false;
        }
        
        return $this->_record->getPrimaryManifest()->eq($value);
    }
    
    public function setValue($value) {
        $record = false;
        
        if($value instanceof self) {
            $record = $value->_record;
            $value = $value->getPrimaryManifest();
        } else if($value instanceof opal\query\record\IRecord) {
            $record = $value;
            $value = $value->getPrimaryManifest();
        } else if(!$value instanceof opal\query\record\IPrimaryManifest) {
            $value = new opal\query\record\PrimaryManifest(array('id'), array(null));
        }
        
        $this->_insertPrimaryManifest = $value;
        $this->_record = $record;
        
        return $this;
    }
    
    public function getValue($default=null) {
        if($this->_record !== false) {
            return $this->_record;
        }
        
        return $default;
    }
    
    public function getValueForStorage() {
        return null;
    }
    
    public function duplicateForChangeList() {
        $output = new self($this->_targetUnitId, $this->_targetField);
        $output->_insertPrimaryManifest = $this->_insertPrimaryManifest;
        return $output;
    }
    
    public function populateInverse(opal\query\record\IRecord $record) {
        if(!$this->_insertPrimaryManifest) {
            $this->_record = $record;
        }
        
        return $this;
    }
    
    
// Tasks
    public function deploySaveTasks(opal\query\record\task\ITaskSet $taskSet, opal\query\record\IRecord $record, $fieldName, opal\query\record\task\ITask $recordTask=null) {
        if($this->_insertPrimaryManifest) {
            if(!$this->_record instanceof opal\query\record\IRecord) {
                $this->prepareValue($record, $fieldName);
            }
            
            $application = $record->getRecordAdapter()->getApplication();
            $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
            
            $query = $targetUnit->fetch();
                    
            foreach($record->getPrimaryManifest()->toArray() as $field => $value) {
                $query->where($this->_targetField.'_'.$field, '=', $value);
            }
            
            $originalRecord = $query->toRow();
            
            if(!$this->_record) {
                $this->_insertPrimaryManifest->updateWith(null);
            } else {
                if($this->_record->isNew()) {
                    $task = $taskSet->insert($this->_record);
                } else {
                    $task = $taskSet->update($this->_record);
                }
                
                if($recordTask && !$this->_insertPrimaryManifest->isNull()) {
                    $task->addDependency(
                        new opal\query\record\task\dependency\UpdateManifestField(
                            $this->_targetField, $recordTask
                        )
                    );
                } else {
                    $this->_record->set($this->_targetField, $this->_insertPrimaryManifest);
                }
            }
            
            if($originalRecord) {
                $originalRecord->set($this->_targetField, null);
                $taskSet->save($originalRecord);
            }
        }
        
        return $this;
    }
    
    public function acceptSaveTaskChanges(opal\query\record\IRecord $record) {
        return $this;
    }
    
    public function deployDeleteTasks(opal\query\record\task\ITaskSet $taskSet, opal\query\record\IRecord $record, $fieldName, opal\query\record\task\ITask $recordTask=null) {
        core\stub($taskSet, $record, $recordTask);
    }
    
    public function acceptDeleteTaskChanges(opal\query\record\IRecord $record) {
        return $this;
    }
    
    
// Dump
    public function getDumpValue() {
        if($this->_record) {
            return $this->_record;
        }
        
        if($this->_insertPrimaryManifest) {
            if($this->_insertPrimaryManifest->countFields() == 1) {
                return $this->_insertPrimaryManifest->getFirstKeyValue();
            }
            
            return $this->_insertPrimaryManifest;
        }
        
        return '['.$this->_targetUnitId.']';
    }
    
    public function getDumpProperties() {
        if($this->_record) {
            return $this->_record;
        }
        
        $output = $this->_targetUnitId;
        
        if($this->_insertPrimaryManifest) {
            $output .= ' : ';
            
            if($this->_insertPrimaryManifest->countFields() == 1) {
                $value = $this->_insertPrimaryManifest->getFirstKeyValue();
                $output .= $value === null ? 'null' : $value;
            } else {
                $t = array();
                
                foreach($this->_insertPrimaryManifest->toArray() as $key => $value) {
                    $t[] = $key.'='.($value === null ? 'null' : $value);
                }
                
                $output .= implode(', ', $t);
            }
        } else {
            $output = '['.$output.']';
        }
        
        return $output;
    }
}
