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
    opal\query\record\ITaskAwareValueContainer, 
    opal\query\record\IPreparedValueContainer, 
    opal\query\record\IIdProviderValueContainer {
        
    protected $_value;
    protected $_record = false;
    protected $_targetUnitId;
    protected $_populateInverseField = null;
    
    public function __construct($value, $targetUnitId, array $primaryFields, $populateInverseField=null) {
        $this->_value = new opal\query\record\PrimaryManifest($primaryFields);
        $this->_targetUnitId = $targetUnitId;
        $this->_populateInverseField = $populateInverseField;
        
        $this->setValue($value);
    } 
    
    public function isPrepared() {
        return $this->_record !== false;
    }
    
    public function prepareValue(opal\query\record\IRecord $record, $fieldName) {
        if($this->_value->isNull()) {
            return $this;
        }
        
        
        $application = $record->getRecordAdapter()->getApplication();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $query = $targetUnit->fetch();
        
        foreach($this->_value->toArray() as $field => $value) {
            $query->where($field, '=', $value);
        }
        
        $this->_record = $query->toRow();
        
        if($this->_record && !empty($this->_populateInverseField)) {
            $inverseValue = $this->_record->getRaw($this->_populateInverseField);
            $inverseValue->populateInverse($record);
        }
        
        return $this;
    }
    
    public function prepareToSetValue(opal\query\record\IRecord $record, $fieldName) {
        return $this;
    }
    
    public function eq($value) {
        if($value instanceof self
        || $value instanceof opal\query\record\IRecord) {
            $value = $value->getPrimaryManifest();
        } else if(!$value instanceof opal\query\record\IPrimaryManifest) {
            try {
                $value = $this->_value->duplicateWith($value);
            } catch(opal\query\record\IException $e) {
                return false;
            }
        }
        
        return $this->_value->eq($value);
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
            if($value === null) {
                $record = null;
            }

            $value = $this->_value->duplicateWith($value);
        }
        
        $this->_value = $value;
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
        return $this->_value;
    }
    
    public function getPrimaryManifest() {
        return $this->_value;
    }

    public function getRawId() {
        if($this->_value) {
            return $this->_value->getFirstKeyValue();
        }

        return null;
    }

    public function getTargetUnitId() {
        return $this->_targetUnitId;
    }
    
    public function duplicateForChangeList() {
        return new self(null, $this->_targetUnitId, $this->_value->getFieldNames());
    }
    
    public function populateInverse(opal\query\record\IRecord $record=null) {
        $this->_record = $record;
        return $this;
    }
    
    
    
// Tasks
    public function deploySaveTasks(opal\query\record\task\ITaskSet $taskSet, opal\query\record\IRecord $record, $fieldName, opal\query\record\task\ITask $recordTask=null) {
        if($this->_record instanceof opal\query\record\IRecord) {
            $task = $this->_record->deploySaveTasks($taskSet);
            
            if($task && $recordTask && $this->_record->isNew()) {
                $recordTask->addDependency(
                    new opal\query\record\task\dependency\UpdateManifestField($fieldName, $task)
                );
            }
        }
        
        return $this;
    }
    
    public function acceptSaveTaskChanges(opal\query\record\IRecord $record) {
        return $this;
    }
    
    public function deployDeleteTasks(opal\query\record\task\ITaskSet $taskSet, opal\query\record\IRecord $record, $fieldName, opal\query\record\task\ITask $recordTask=null) {
        //core\stub($taskSet, $record, $recordTask);
    }
    
    public function acceptDeleteTaskChanges(opal\query\record\IRecord $record) {
        return $this;
    }
    
    
// Dump
    public function getDumpValue() {
        if($this->_record) {
            return $this->_record;
        }
        
        if($this->_value->countFields() == 1) {
            return $this->_value->getFirstKeyValue();
        }
        
        return $this->_value;
    }

    public function getDumpProperties() {
        if($this->_record) {
            return $this->_record;
        }
        
        $output = $this->_targetUnitId.' : ';
        
        if($this->_value->countFields() == 1) {
            $value = $this->_value->getFirstKeyValue();
            
            if($value === null) {
                $output .= 'null';
            } else {
                $output .= $value;
            }
        } else {
            $t = array();
            
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
    