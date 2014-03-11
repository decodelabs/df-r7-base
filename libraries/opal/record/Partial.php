<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record;

use df;
use df\core;
use df\opal;
use df\user;

class Partial implements IPartial, core\IDumpable {
    
    use TRecordAdapterProvider;
    use TPrimaryKeySetProvider;
    use TAccessLockProvider;
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_AssociativeValueMap;
    use user\TAccessLock;

    protected $_isBridge = false;

    public function __construct(opal\query\IAdapter $adapter=null, $row=null, array $fields=null) {
        $this->_adapter = $adapter;
        
        if(!empty($fields)) { 
            $this->_collection = array_fill_keys($fields, null);
        }
                
        if($row !== null) {
            $this->import($row);
        }
    }

    public function setRecordAdapter(opal\query\IAdapter $adapter) {
        $this->_adapter = $adapter;
        return $this;
    }

    public function isBridge($flag=null) {
        if($flag !== null) {
            $this->_isBridge = (bool)$flag;
            return $this;
        }

        return $this->_isBridge;
    }


    public function getReductiveIterator() {
        return new ReductiveMapIterator($this);
    }


    protected function _buildPrimaryKeySet(array $fields, $includeChanges=true) {
        $values = array();
        
        foreach($fields as $field) {
            if(isset($this->_collection[$field])) {
                $values[$field] = $this->_collection[$field];
            } else {
                $values[$field] = null;
            }
            
            if($values[$field] instanceof IValueContainer) {
                $values[$field] = $values[$field]->getValueForStorage();
            }
        }
        
        return new PrimaryKeySet($fields, $values); 
    }

    public function getValuesForStorage() {
        $output = $this->_collection;

        foreach(opal\schema\Introspector::getPrimaryFields($this->_adapter) as $field) {
            unset($output[$field]);
        }

        return $output;
    }

    public function populateWithPreparedData(array $row) {
        foreach($row as $key => $value) {
            $this->_collection[$key] = $value;
        }

        return $this;
    }
    
    public function populateWithRawData($row) {
        return $this->import($row);
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
                'Could not import to partial - input data cannot be converted to an array'
            );
        }
        
        
        // Sanitize values from adapter
        $temp = $row;

        foreach(opal\schema\Introspector::getFieldProcessors($this->_adapter, array_keys($row)) as $name => $field) {
            if(isset($temp[$name])) {
                $value = $temp[$name];
            } else {
                $value = null;
            }

            $row[$name] = $field->sanitizeValue($value);
        }
        
        
        // Sanitize values from extension
        foreach($row as $key => $value) {
            $this->_collection[$key] = $value;
        }
        
        return $this;
    }


// Policy
    public function getEntityLocator() {
        if(!$this->_adapter instanceof core\policy\IParentEntity) {
            throw new core\policy\EntityNotFoundException(
                'Partial adapter is not a policy entity handler'
            );
        }

        if($this->_adapter instanceof core\policy\IActiveParentEntity) {
            return $this->_adapter->getSubEntityLocator($this);
        }

        $keySet = $this->getPrimaryKeySet();
        $id = $keySet->getEntityId();

        $output = $this->_adapter->getEntityLocator();
        $output->addNode(new core\policy\entity\LocatorNode(null, 'Record', $id));
        
        return $output;
    }


// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}