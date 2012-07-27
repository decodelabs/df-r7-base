<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record;

use df;
use df\core;
use df\opal;

class PrimaryManifest implements IPrimaryManifest, core\IDumpable {
    
    const COMBINE_SEPARATOR = '+';
    
    protected $_keys = array();
    
    public function __construct(array $fields, $values=array()) {
        $this->_keys = array_fill_keys($fields, null);
        $this->updateWith($values);
    }
    
    public function getKeys() {
        return $this->_keys;
    }
    
    public function __toString() {
        foreach($this->_keys as $key => $value) {
            return (string)$value;
        }
        
        return '';
    }
    
    public function toArray() {
        $output = array();
        
        foreach($this->_keys as $key => $value) {
            if($value instanceof self) {
                foreach($value->toArray() as $subKey => $subValue) {
                    $output[$key.'_'.$subKey] = $subValue;
                }
            } else {
                $output[$key] = $value;
            }
        }
        
        return $output;
    }

    public function getKeyMap($fieldName) {
        $output = array();

        foreach($this->_keys as $key => $value) {
            if($value instanceof self) {
                foreach($value->toArray() as $subKey => $subValue) {
                    $output[$key.'_'.$subKey] = $fieldName.'_'.$key.'_'.$subKey;
                }
            } else {
                $output[$key] = $fieldName.'_'.$key;
            }
        }

        return $output;
    }
    
    public function getIntrinsicFieldMap($fieldName) {
        $output = array();

        foreach($this->_keys as $key => $value) {
            if($value instanceof self) {
                foreach($value->toArray() as $subKey => $subValue) {
                    $output[$fieldName.'_'.$key.'_'.$subKey] = $subValue;
                }
            } else {
                $output[$fieldName.'_'.$key] = $value;
            }
        }

        return $output;
    }

    public function updateWith($values) {
        $fields = array_keys($this->_keys);

        if($values instanceof self) {
            $values = $values->toArray();
        }
        
        if(!$values instanceof IRecord && !is_array($values)) {
            if($values === null || count($fields) == 1) {
                $values = array_fill_keys($fields, $values);
            } else {
                throw new InvalidArgumentException(
                    'Primary manifest values do not map to keys'
                );
            }
        }
        
        foreach($fields as $field) {
            if(isset($values[$field])) {
                $this->_keys[$field] = $values[$field];
            } else {
                $this->_keys[$field] = null;
            }
        }
        
        return $this;
    }
    
    public function countFields() {
        return count($this->_keys);
    }
    
    public function getFieldNames() {
        return array_keys($this->_keys);
    }
    
    public function isNull() {
        foreach($this->_keys as $value) {
            if($value === null) {
                return true;
            }

            if($value instanceof IPrimaryManifest && $value->isNull()) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getCombinedId() {
        $strings = array();
        
        foreach($this->_keys as $key) {
            if($key instanceof IRecord) {
                $key = $key->getPrimaryManifest();
            }
            
            if($key instanceof self) {
                $key = '['.$key->getCombinedId().']';
            }
            
            $strings[] = (string)$key;
        }
        
        return implode(self::COMBINE_SEPARATOR, $strings);
    }
    
    public function getFirstKeyValue() {
        foreach($this->_keys as $value) {
            return $value;
        }
    }
    
    public function duplicateWith($values) {
        if($values instanceof IPrimaryManifest) {
            return $values;
        }
        
        return new self(array_keys($this->_keys), $values);
    }
    
    public function eq(IPrimaryManifest $manifest) {
        foreach($this->_keys as $key => $value) {
            if(!isset($manifest->_keys[$key])
            || $manifest->_keys[$key] !== $value) {
                return false;
            }
        }
        
        return true;
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->_keys;
    }
}
