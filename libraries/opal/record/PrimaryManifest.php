<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record;

use df;
use df\core;
use df\opal;

class PrimaryManifest implements IPrimaryManifest, core\IDumpable {
    
    const COMBINE_SEPARATOR = '+';
    
    protected $_keys = array();

    public static function fromEntityId($id) {
        if(substr($id, 0, 9) != 'manifest?') {
            throw new InvalidArgumentException(
                'Invalid entity id: '.$id
            );
        }

        $id = substr($id, 9);
        $tree = core\collection\Tree::fromArrayDelimitedString($id);
        $values = array();

        foreach($tree as $key => $value) {
            if(substr($value, 0, 10) == '[manifest?') {
                $value = self::fromEntityId(substr($value, 1, -1));
            }

            $values[$key] = $value;
        }

        return new self(array_keys($values), $values);
    }
    
    public function __construct(array $fields, $values=array()) {
        $this->_keys = array_fill_keys($fields, null);
        $this->updateWith($values);
    }
    
    public function __clone() {
        foreach($this->_keys as $key => $value) {
            if($value instanceof self) {
                $this->_keys[$key] = clone $value;
            }
        }
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
    
    public function getIntrinsicFieldMap($fieldName=null) {
        if($fieldName === null) {
            return $this->toArray();
        }
        
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
                $value = $values[$field];

                if($value instanceof IRecord) {
                    $value = $value->getPrimaryManifest();
                }
            } else {
                $value = null;
            }

            if($this->_keys[$field] instanceof self) {
                $this->_keys[$field]->updateWith($value);
            } else {
                $this->_keys[$field] = $value;
            }
        }
        
        return $this;
    }
    
    public function countFields() {
        return count($this->_keys);
    }
    
    public function getFieldNames() {
        return array_keys($this->toArray());
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

    public function getEntityId() {
        $returnFirst = false;

        if(count($this->_keys) == 1) {
            $returnFirst = true;
        }

        $output = new core\collection\Tree();
        
        foreach($this->_keys as $key => $value) {
            if($value instanceof IRecord) {
                $returnFirst = false;
                $value = $value->getPrimaryManifest();
            }
            
            if($value instanceof self) {
                $returnFirst = false;
                $value = '['.$value->getEntityId().']';
            }

            if($returnFirst) {
                return (string)$value;
            }
            
            $output->{$key} = (string)$value;
        }

        return 'manifest?'.$output->toArrayDelimitedString();
    }
    
    public function getValue() {
        if(count($this->_keys) == 1) {
            return $this->getFirstKeyValue();
        }

        return $this->_keys;
    }

    public function getFirstKeyValue() {
        foreach($this->_keys as $value) {
            return $value;
        }
    }

    public function duplicateWith($values) {
        $output = clone $this;
        $output->updateWith($values);
        return $output;
    }
    
    public function eq(IPrimaryManifest $manifest) {
        foreach($this->_keys as $key => $value) {
            if(!array_key_exists($key, $manifest->_keys)
            || $manifest->_keys[$key] !== $value) {
                return false;
            }
        }
        
        return true;
    }


// Array access
    public function offsetSet($key, $value) {
        $this->_keys[$key] = $value;
        return $this;
    }

    public function offsetGet($key) {
        if(isset($this->_keys[$key])) {
            return $this->_keys[$key];
        }
    }

    public function offsetExists($key) {
        return isset($this->_keys[$key]);
    }

    public function offsetUnset($key) {
        unset($this->_keys[$key]);
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->_keys;
    }
}
