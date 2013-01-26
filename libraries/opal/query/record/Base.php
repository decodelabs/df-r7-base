<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record;

use df;
use df\core;
use df\opal;
use df\user;

class Base implements IRecord, \Serializable, core\IDumpable {
    
    use core\collection\TExtractList;
    use user\TAccessLock;
    
    protected $_values = array();
    protected $_changes = array();
    protected $_isPopulated = false;
    protected $_adapter;

    private $_primaryFields = false;
    
    public function __construct(opal\query\IAdapter $adapter, $row=null, array $fields=null) {
        $this->_adapter = $adapter;
        
        if($fields === null && $adapter instanceof opal\query\IIntegralAdapter) {
            $fields = $adapter->getRecordFieldNames();
        }
        
        if(!empty($fields)) { 
            $this->_values = array_fill_keys($fields, null);
        }
        
        
        // Prepare value slots
        if($this->_adapter instanceof opal\query\IIntegralAdapter) {
            $fieldProcessors = $this->_adapter->getQueryResultValueProcessors();
            
            if(!empty($fieldProcessors)) {
                foreach($fieldProcessors as $name => $field) {
                    $this->_values[$name] = $field->inflateValueFromRow($name, array(), $this);
                }
            }
        }
        
        
        // Import initial values
        if($row !== null) {
            $this->import($row);
        }
    }
    
    public function serialize() {
        return serialize(array(
            'adapter' => $this->_adapter->getQuerySourceId(),
            'values' => $this->getValuesForStorage()
        ));
    }
    
    public function unserialize($data) {
        $values = unserialize($data);
        $adapter = core\policy\Manager::getInstance()->fetchEntity($values['adapter']);
        
        $this->__construct($adapter);
        $this->populateWithRawData($values['values']);
        
        return $this;
    }
    
    public function getRecordAdapter() {
        return $this->_adapter;
    }
    
    public function isNew() {
        return !$this->_isPopulated;
    }
    
    public function makeNew(array $newValues=null) {
        $this->_isPopulated = false;

        // Clear primary values
        if(null !== ($fields = $this->_getPrimaryFields())) {
            foreach($fields as $field) {
                unset($this->_changes[$field]);

                if($this->_values[$field] instanceof IValueContainer) {
                    $this->_values[$field]->setValue(null);
                } else {
                    $this->_values[$field] = null;
                }
            }
        }

        if($newValues !== null) {
            $this->import($newValues);
        }

        return $this;
    }

    public function spawnNew(array $newValues=null) {
        $output = clone $this;
        return $output->makeNew($newValues);
    }
    
    public function getPrimaryManifest() {
        $fields = $this->_getPrimaryFields();

        if($fields === null) {
            throw new LogicException(
                'Record type '.$this->_adapter->getQuerySourceId().' has no primary fields'
            );
        }

        $values = array();
        
        foreach($fields as $field) {
            if(array_key_exists($field, $this->_changes)) {
                $values[$field] = $this->_changes[$field];
            } else if(isset($this->_values[$field])) {
                $values[$field] = $this->_values[$field];
            } else {
                $values[$field] = null;
            }
            
            if($values[$field] instanceof IValueContainer) {
                $values[$field] = $values[$field]->getValueForStorage();
            }
        }
        
        return new PrimaryManifest($fields, $values); 
    }
    
    protected function _getPrimaryFields() {
        if($this->_primaryFields === false) {
            if($this->_adapter instanceof opal\query\IIntegralAdapter) {
                $this->_primaryFields = $this->_adapter->getRecordPrimaryFieldNames();
            } else {
                $this->_primaryFields = null;
            }
        }
        
        return $this->_primaryFields;
    }
    
    
// Changes
    public function hasChanged($field=null) {
        if($field !== null) {
            return array_key_exists($field, $this->_changes);
        }
        
        return !empty($this->_changes);
    }
    
    public function clearChanges() {
        $this->_changes = array();
        return $this;
    }

    public function countChanges() {
        return count($this->_changes);
    }

    public function getChanges() {
        return $this->_changes;
    }
    
    public function getChangesForStorage() {
        $output = array();
        
        foreach($this->_changes as $key => $value) {
            if($value instanceof IValueContainer) {
                $value = $value->getValueForStorage();
            } else if($value instanceof IRecord) {
                $value = $value->getPrimaryManifest();
            }
            
            $output[$key] = $this->_deflateValue($key, $value);
        }
        
        return $output;
    }
    
    public function getValuesForStorage() {
        $output = array_merge($this->_values, $this->_changes);
        
        foreach($output as $key => $value) {
            if($value instanceof IValueContainer) {
                $value = $value->getValueForStorage();
            } else if($value instanceof IRecord) {
                $value = $value->getPrimaryManifest();
            }
            
            $output[$key] = $this->_deflateValue($key, $value);
        }
        
        return $output;
    }
    
    public function getUpdatedValues() {
        $output = array();
        
        foreach($this->_changes as $key => $value) {
            if(array_key_exists($key, $this->_values) && $value !== null) {
                if($value instanceof IValueContainer) {
                    $value = $value->getValue();
                }
                
                $output[$key] = $value;
            }
        }
        
        return $output;
    }
    
    public function getUpdatedValuesForStorage() {
        $output = array();
        
        foreach($this->_changes as $key => $value) {
            if(array_key_exists($key, $this->_values) && $value !== null) {
                if($value instanceof IValueContainer) {
                    $value = $value->getValueForStorage();
                } else if($value instanceof IRecord) {
                    $value = $value->getPrimaryManifest();
                }
                
                $output[$key] = $this->_deflateValue($key, $value);
            }
        }
        
        return $output;
    }
    
    public function getAddedValues() {
        $output = array();
        
        foreach($this->_changes as $key => $value) {
            if(!array_key_exists($key, $this->_values) && $this->_changes[$key] !== null) {
                if($value instanceof IValueContainer) {
                    $value = $value->getValue();
                }
                
                $output[$key] = $value;
            }
        }
        
        return $output;
    }
    
    public function getAddedValuesForStorage() {
        $output = array();
        
        foreach($this->_changes as $key => $value) {
            if(!array_key_exists($key, $this->_values) && $this->_changes[$key] !== null) {
                if($value instanceof IValueContainer) {
                    $value = $value->getValueForStorage();
                } else if($value instanceof IRecord) {
                    $value = $value->getPrimaryManifest();
                }
                
                $output[$key] = $this->_deflateValue($key, $value);;
            }
        }
        
        return $output;
    }
    
    public function getOriginalValues() {
        $output = array();
        
        foreach($this->_values as $key => $value) {
            if($value instanceof IValueContainer) {
                $value = $value->getValue();
            }
            
            $output[$key] = $value;
        }
        
        return $output;
    }
    
    public function getOriginalValuesForStorage() {
        $output = array();
        
        foreach($this->_values as $key => $value) {
            if($value instanceof IValueContainer) {
                $value = $value->getValueForStorage();
            } else if($value instanceof IRecord) {
                $value = $value->getPrimaryManifest();
            }
            
            $output[$key] = $this->_deflateValue($key, $value);
        }
        
        return $output;
    }
    
    public function acceptChanges($insertId=null, array $insertData=null) {
        $this->_values = array_merge($this->_values, $this->_changes);
        $this->clearChanges();
        
        $this->_isPopulated = false;
        
        if($insertId !== null && (null !== ($primaryFields = $this->_getPrimaryFields()))) {
            if(!$insertId instanceof IPrimaryManifest) {
                if(count($primaryFields) > 1) {
                    // Cant do anything here??
                } else {
                    $insertId = new PrimaryManifest($primaryFields, array($primaryFields[0] => $insertId));
                }
            }
            
            foreach($insertId->getKeys() as $field => $value) {
                $value = $this->_inflateValue($field, $value);
                
                if($this->_values[$field] instanceof IValueContainer) {
                    $this->_values[$field]->setValue($value);
                } else {
                    $this->_values[$field] = $value;
                }
            }
        }


        foreach($this->_values as $key => $value) {
            if($value === null && isset($insertData[$key])) {
                $this->_values[$key] = $value = $insertData[$key];
            }
            
            if($value instanceof ITaskAwareValueContainer) {
                $value->acceptSaveTaskChanges($this);
            }
        }
        
        $this->_isPopulated = true;
        
        return $this;
    }


    public function markAsChanged($field) {
        if(!array_key_exists($field, $this->_changes)) {
            $oldVal = null;
            
            if(isset($this->_values[$field])) { 
                $oldVal = $this->_values[$field];
                
                if($oldVal instanceof IValueContainer) {
                    $oldVal = $oldVal->duplicateForChangeList();
                }
            } 
            
            $this->_changes[$field] = $oldVal;
            //$this->_onValueChange($field, $oldVal, $oldVal);
        }
        
        return $this;
    }


    public function populateWithPreparedData(array $row) {
        if($this->_isPopulated) {
            throw new RuntimeException(
                'Record has already been populated'
            );
        }

        foreach($row as $key => $value) {
            $this->_values[$key] = $this->_inflateValue($key, $value);
        }
        
        $this->_isPopulated = true;
        return $this;
    }
    
    public function populateWithRawData($row) {
        if($this->_isPopulated) {
            throw new RuntimeException(
                'Record has already been populated'
            );
        }
        
        if($row instanceof opal\query\IDataRowProvider) {
            $row = $row->toDataRowArray();
        } else if($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        }
        
        if(!is_array($row)) {
            throw new InvalidArgumentException(
                'Could not populate record - input data cannot be converted to an array'
            );
        }
        
        
        // Inflate values from adapter
        if($this->_adapter instanceof opal\query\IIntegralAdapter) {
            $fieldProcessors = $this->_adapter->getQueryResultValueProcessors();
            
            if(!empty($fieldProcessors)) {
                $temp = $row;
                $row = array();
                
                foreach($fieldProcessors as $name => $field) {
                    $row[$name] = $field->inflateValueFromRow($name, $temp, $this);
                }
            }
        }
        
        
        // Inflate values from extension
        foreach($row as $key => $value) {
            $this->_values[$key] = $this->_inflateValue($key, $value);
        }
        
        
        $this->_isPopulated = true;
        return $this;
    }


// Collection
    public function import($row) {
        if($row instanceof opal\query\IDataRowProvider) {
            $row = $row->toDataRowArray();
        } else if($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        }
        
        if(!is_array($row)) {
            throw new InvalidArgumentException(
                'Could not import to record - input data cannot be converted to an array'
            );
        }
        
        
        // Sanitize values from adapter
        if($this->_adapter instanceof opal\query\IIntegralAdapter) {        
            $fieldProcessors = $this->_adapter->getQueryResultValueProcessors(array_keys($row));
            
            if(!empty($fieldProcessors)) {
                $temp = $row;
                $row = array();
                
                foreach($fieldProcessors as $name => $field) {
                    if(isset($temp[$name])) {
                        $value = $temp[$name];
                    } else {
                        $value = null;
                    }

                    $row[$name] = $field->sanitizeValue($value, true);
                }
            }
        }
        
        
        // Sanitize values from extension
        foreach($row as $key => $value) {
            $this->_changes[$key] = $this->_sanitizeValue($key, $value);
        }
        
        return $this;
    }
    
    public function isEmpty() {
        return empty($this->_values) && empty($this->_changes);
    }
    
    public function clear() {
        $this->_values = array();
        $this->_changes = array();
        $this->_isPopulated = false;
        
        return $this;
    }
    
    public function extract() {
        core\stub();
    }
    
    public function count() {
        return count(array_merge($this->_values, $this->_changes));
    }
    
    public function toArray() {
        $output = array_merge($this->_values, $this->_changes);
        
        foreach($output as $key => $value) {
            if($value instanceof IValueContainer) {
                $value = $value->getValue();
            }
            
            $output[$key] = $value;
        }
        
        return $output;
    }
    
    
// Storage
    public function save() {
        $taskSet = new opal\query\record\task\TaskSet();
        $this->deploySaveTasks($taskSet);
        $taskSet->execute();
        
        return $this;
    }
    
    public function delete() {
        $taskSet = new opal\query\record\task\TaskSet();
        $this->deployDeleteTasks($taskSet);
        $taskSet->execute();
        
        return $this;
    }
    
    public function deploySaveTasks(opal\query\record\task\ITaskSet $taskSet) {
        $recordTask = null;
        
        if($taskSet->isRecordQueued($this)) {
            return $recordTask;
        }
        
        if($this->hasChanged()) {
            $recordTask = $taskSet->save($this);
        } else {
            $taskSet->setRecordAsQueued($this);
        }
        
        foreach(array_merge($this->_values, $this->_changes) as $key => $value) {
            if($value instanceof ITaskAwareValueContainer) {
                $value->deploySaveTasks($taskSet, $this, $key, $recordTask);
            }
        }
        
        return $recordTask;
    }
    
    public function deployDeleteTasks(opal\query\record\task\ITaskSet $taskSet) {
        $recordTask = null;

        if(!$this->isNew()) {
            if($taskSet->isRecordQueued($this)) {
                return $recordTask;
            }
            
            $recordTask = $taskSet->delete($this);

            foreach(array_merge($this->_values, $this->_changes) as $key => $value) {
                if($value instanceof ITaskAwareValueContainer) {
                    $value->deployDeleteTasks($taskSet, $this, $key, $recordTask);
                }
            }
        }
        
        return $recordTask;
    }
    
    
// Access
    public function __set($key, $value) {
        return $this->offsetSet($key, $value);
    }
    
    public function __get($key) {
        $output = null;
        
        if(array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } else if(array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }
        
        if($output instanceof IPreparedValueContainer && !$output->isPrepared()) {
            $output->prepareValue($this, $key);
        }
        
        return $this->_prepareValueForUser($key, $output);
    }
    
    public function getRaw($key) {
        $output = null;
        
        if(array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } else if(array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }
        
        return $output;
    }

    public function getOriginal($key) {
        $output = null;

        if(array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }

        return $this->_prepareOutputValue($key, $output);
    }


    public function set($key, $value) {
        return $this->offsetSet($key, $value);
    }
    
    public function get($key, $default=null) {
        return $this->offsetGet($key, $default);
    }
    
    public function has($key) {
        return $this->offsetExists($key);
    }
    
    public function remove($key) {
        return $this->offsetUnset($key);
    }
    
    public function offsetSet($key, $value) {
        // Sanitize value from extension
        $value = $this->_sanitizeValue($key, $value);
        
        // Sanitize value from extension
        $fieldProcessor = null;

        if($this->_adapter instanceof opal\query\IIntegralAdapter) {
            $fieldProcessors = $this->_adapter->getQueryResultValueProcessors(array($key));

            if(isset($fieldProcessors[$key])) {
                $fieldProcessor = $fieldProcessors[$key];
                $value = $fieldProcessors[$key]->sanitizeValue($value, false);
            }
        }
        
        
        
        if(array_key_exists($key, $this->_changes)) {
            $oldVal = $this->_changes[$key];
        } else {
            $oldVal = null;
            $isEqual = null;
            
            if(isset($this->_values[$key])) { 
                $oldVal = $this->_values[$key];
            } 

            if($oldVal instanceof IValueContainer) {
                if($isEqual = $oldVal->eq($value)) {
                    return $this;
                }
            }


            if($isEqual === null && $fieldProcessor) {
                if($isEqual = $fieldProcessor->compareValues($oldVal, $value)) {
                    return $this;
                }
            }

            if($isEqual === null && $value1 === $value2) {
                return $this;
            }
            
            if($oldVal instanceof IValueContainer) {
                $oldVal = $oldVal->duplicateForChangeList();
            }
            
            $this->_changes[$key] = $oldVal;
        }
        
        if($this->_changes[$key] instanceof IValueContainer) {
            $this->_changes[$key]->prepareToSetValue($this, $key);
            $this->_changes[$key]->setValue($value);
        } else {
            $this->_changes[$key] = $value;
        }
        
        $this->_onValueChange($key, $oldVal, $value);
        
        return $this;
    }
    
    public function offsetGet($key, $default=null) {
        $output = null;
        
        if(array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } else if(array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }
        
        return $this->_prepareOutputValue($key, $output, $default);
    }

    protected function _prepareOutputValue($key, $value, $default=null) {
        if($value instanceof IValueContainer) {
            if($value instanceof IPreparedValueContainer && !$value->isPrepared()) {
                $value->prepareValue($this, $key);
            }
            
            $value = $value->getValue($default);
        }
        
        $value = $this->_prepareValueForUser($key, $value);
        
        if($value === null) {
            $value = $default;
        }
        
        return $value;
    }
    
    public function offsetExists($key) {
        return array_key_exists($key, $this->_changes) 
            || array_key_exists($key, $this->_values);
    }
    
    public function offsetUnset($key) {
        $output = null;
        
        if(array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } else if(array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }
        
        $this->_changes[$key] = null;
        //unset($this->_values[$key]);
        
        $this->_onValueRemove($key, $output);
        
        return $this;
    }
    
    
    protected function _inflateValue($key, $value) {
        return $value;
    }
    
    protected function _deflateValue($key, $value) {
        return $value;
    }
    
    protected function _sanitizeValue($key, $value) {
        return $value;
    }
    
    protected function _prepareValueForUser($key, $value) {
        return $value;
    }
    
    
    protected function _onValueChange($key, $oldValue, $newValue) {}
    protected function _onValueRemove($key, $oldValue) {}
    


// Access
    public function getAccessLockDomain() {
        return $this->_adapter->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action=null) {
        return $this->_adapter->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null) {
        return $this->_adapter->getDefaultAccess($action);
    }

    public function getAccessLockId() {
        return $this->_adapter->getAccessLockId();
    }


// Policy
    public function getEntityLocator() {
        if(!$this->_adapter instanceof core\policy\IParentEntity) {
            throw new core\policy\EntityNotFoundException(
                'Record adapter is not a policy entity handler'
            );
        }

        $manifest = $this->getPrimaryManifest();
        $id = $manifest->getEntityId();

        $output = $this->_adapter->getEntityLocator();
        $output->addNode(new core\policy\EntityLocatorNode(null, 'Record', $id));
        
        return $output;
    }

    
// Dump
    public function getDumpProperties() {
        $output = array();
        
        foreach(array_merge($this->_values, $this->_changes) as $key => $value) {
            if($value instanceof IValueContainer) {
                $value = $value->getDumpValue();
            }
            
            if(array_key_exists($key, $this->_changes)) {
                if(!array_key_exists($key, $this->_values)) {
                    $key = '+ '.$key;
                } else if($this->_changes[$key] === null) {
                    if(isset($this->_values[$key])) {
                        $value = $this->_values[$key];
                    }
                    
                    if($value !== null) {
                        $key = '- '.$key;
                    }
                } else {
                    $key .= ' *';
                }
            }
            
            $output[$key] = $value;
        }
        
        return $output;
    }
}
