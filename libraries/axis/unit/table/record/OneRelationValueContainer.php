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

class OneRelationValueContainer implements 
    opal\record\ITaskAwareValueContainer, 
    opal\record\IPreparedValueContainer, 
    opal\record\IIdProviderValueContainer {
        
    protected $_value;
    protected $_record = false;
    protected $_parentRecord;
    protected $_field;
    
    public function __construct(axis\schema\IRelationField $field, opal\record\IRecord $parentRecord=null, $value=null) {
        $this->_field = $field;
        $this->_value = $field->getTargetRelationManifest()->toPrimaryKeySet();

        $this->setValue($value);
        $this->_applyInversePopulation($parentRecord);
    } 
    
    public function isPrepared() {
        return $this->_record !== false;
    }
    
    public function prepareValue(opal\record\IRecord $record, $fieldName) {
        if($this->_value->isNull()) {
            return $this;
        }
        
        $localUnit = $record->getRecordAdapter();
        $application = $localUnit->getApplication();
        $clusterId = $this->_field->isOnGlobalCluster() ? null : $localUnit->getClusterId();
        $targetUnit = axis\Model::loadUnitFromId($this->_field->getTargetUnitId(), $clusterId, $application);
        $query = $targetUnit->fetch();

        foreach($this->_value->toArray() as $field => $value) {
            $query->where($field, '=', $value);
        }
        
        $this->_record = $query->toRow();
        $this->_applyInversePopulation($record);
        
        return $this;
    }

    protected function _applyInversePopulation($parentRecord) {
        if($parentRecord && $this->_record 
        && $this->_field instanceof opal\schema\IInverseRelationField) {
            $inverseValue = $this->_record->getRaw($this->_field->getTargetField());
            $inverseValue->populateInverse($parentRecord);
        }
    }
    
    public function prepareToSetValue(opal\record\IRecord $record, $fieldName) {
        $this->_parentRecord = $record;
        return $this;
    }
    
    public function eq($value) {
        if($value instanceof self) {
            $value = $value->getPrimaryKeySet();
        } else if($value instanceof opal\record\IRecord) {
            if($value->isNew()) {
                return false;
            }

            $value = $value->getPrimaryKeySet();
        } else if(!$value instanceof opal\record\IPrimaryKeySet) {
            try {
                $value = $this->_value->duplicateWith($value);
            } catch(opal\record\IException $e) {
                return false;
            }
        }

        return $this->_value->eq($value);
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
            if($value === null) {
                $record = null;
            }
        }

        $value = $this->_value->duplicateWith($value);

        $this->_value = $value;
        $this->_record = $record;

        if($record && $this->_parentRecord) {
            $this->_applyInversePopulation($this->_parentRecord);
            $this->_parentRecord = null;
        }
        
        return $this;
    }
    
    public function getValue($default=null) {
        if($this->_record !== false) {
            return $this->_record;
        }

        if($this->_value && !$this->_value->isNull()) {
            return $this->_value->getValue();
        }
        
        return $default;
    }
    
    public function getValueForStorage() {
        if($this->_record) {
            return $this->_value->duplicateWith($this->_record->getPrimaryKeySet());
        } else {
            return $this->_value;
        }
    }
    
    public function getPrimaryKeySet() {
        return $this->_value;
    }

    public function getRawId() {
        if($this->_record) {
            return $this->_record->getPrimaryKeySet()->getFirstKeyValue();
        }

        if($this->_value) {
            return $this->_value->getFirstKeyValue();
        }

        return null;
    }

    public function getTargetUnitId() {
        return $this->_field->getTargetUnitId();
    }

    public function getTargetUnit($clusterId=null) {
        $application = null;

        if($this->_record) {
            $localUnit = $this->_record->getRecordAdapter();
            $application = $localUnit->getApplication();

            if($clusterId === null && !$this->_field->isOnGlobalCluster()) {
                $clusterId = $localUnit->getClusterId();
            }
        }
        
        return $this->_getTargetUnit($clusterId, $application);
    }

    protected function _getTargetUnit($clusterId=null, core\IApplication $application=null) {
        return axis\Model::loadUnitFromId($this->_field->getTargetUnitId(), $clusterId, $application);
    }
    
    public function duplicateForChangeList() {
        return new self($this->_field);
    }
    
    public function populateInverse(opal\record\IRecord $record=null) {
        $this->_record = $record;
        return $this;
    }

    public function __toString() {
        return (string)$this->getRawId();
    }
    
    
    
// Tasks
    public function deploySaveTasks(opal\record\task\ITaskSet $taskSet, opal\record\IRecord $record, $fieldName, opal\record\task\ITask $recordTask=null) {
        if($this->_record instanceof opal\record\IRecord) {
            $task = $this->_record->deploySaveTasks($taskSet);

            if(!$task && $this->_record->isNew()) {
                $task = $taskSet->getTask($this->_record);
            }

            if($task && $recordTask && $this->_record->isNew()) {
                $recordTask->addDependency(
                    new opal\record\task\dependency\UpdateKeySetField($fieldName, $task)
                );
            }
        }
        
        return $this;
    }
    
    public function acceptSaveTaskChanges(opal\record\IRecord $record) {
        return $this;
    }
    
    public function deployDeleteTasks(opal\record\task\ITaskSet $taskSet, opal\record\IRecord $record, $fieldName, opal\record\task\ITask $recordTask=null) {
        //core\stub($taskSet, $record, $recordTask);
    }
    
    public function acceptDeleteTaskChanges(opal\record\IRecord $record) {
        return $this;
    }
    
    
// Dump
    public function getDumpValue() {
        if($this->_record) {
            return $this->_record;
        }
        
        /*
        if($this->_value->countFields() == 1) {
            return $this->_value->getFirstKeyValue();
        }
        */
        
        return $this->_value;
    }

    public function getDumpProperties() {
        if($this->_record) {
            return $this->_record;
        }
        
        $output = $this->_field->getTargetUnitId().' : ';
        
        if($this->_value->countFields() == 1) {
            $value = $this->_value->getFirstKeyValue();
            
            if($value === null) {
                $output .= 'null';
            } else {
                $output .= $value;
            }
        } else {
            $t = [];
            
            foreach($this->_value->toArray() as $key => $value) {
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
        
        return $output;
    }
}
    