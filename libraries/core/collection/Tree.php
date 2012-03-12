<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class Tree implements ITree, ISeekable, ISortable, IAggregateIteratorCollection, \Serializable, core\IDumpable {
    
    use TArrayCollection;
    use TArrayCollection_Seekable;
    use TValueMapArrayAccess;
    use TArrayCollection_ValueContainerSortable;
    
    protected $_value;
    
    public static function fromArrayDelimitedString($string, $setDelimiter='&', $valueDelimiter='=') {
        $output = new self();
        $parts = explode($setDelimiter, $string);
        
        foreach($parts as $part) {
            $valueParts = explode($valueDelimiter, trim($part), 2);
            
            $key = str_replace(array('[', ']'), array('.', ''), urldecode(array_shift($valueParts)));
            $value = urldecode(array_shift($valueParts));
            
            $output->getNestedChild($key)->setValue($value);
        }
        
        return $output;
    }
    
    public function __construct($input=null, $value=null) {
        $this->setValue($value);
        
        if($input !== null) {
            $this->import($input);
        }
    }
    
    
    public function import($input) {
        if($input instanceof ITree) {
            return $this->importTree($input);
        }
        
        if($input instanceof core\IArrayProvider) {
            $input = $input->toArray();
        }
        
        if(is_array($input)) {
            foreach($input as $key => $value) {
                $this->__set($key, $value);
            }
        } else {
            $this->setValue($input);
        }
        
        return $this;
    }
    
    public function importTree(ITree $input) {
        $this->_value = $input->_value;
        
        foreach($input->_collection as $key => $child) {
            unset($this->_collection[$key]);
            $this->{$key}->importTree($child);
        }

        return $this;
    }
    
    public function merge(ITree $input) {
        $this->_value = $input->_value;
        
        foreach($input->_collection as $key => $child) {
            $this->{$key}->importTree($child);
        }

        return $this;
    }
    
    
// Serialize
    public function serialize() {
        return serialize($this->_getSerializeValues());
    }
    
    protected function _getSerializeValues() {
        $output = array();
        
        if($this->_value !== null) {
            $output['vl'] = $this->_value;
        }
        
        if(!empty($this->_collection)) {
            $children = array();
            
            foreach($this->_collection as $key => $child) {
                $children[$key] = $child->_getSerializeValues();
            }
            
            $output['cd'] = $children;
        }
        
        if(empty($output)) {
            $output = null;
        }
        
        return $output;
    }
    
    public function unserialize($data) {
        if(is_array($values = unserialize($data))) {
            $this->_setUnserializedValues($values);
        }
        
        return $this;
    }
    
    protected function _setUnserializedValues(array $values) {
        if(isset($values['vl'])) {
            $this->_value = $values['vl'];
        }
        
        if(isset($values['cd'])) {
            $class = get_class($this);
            
            foreach($values['cd'] as $key => $childData) {
                $child = new $class();
                
                if(!empty($childData)) {
                    $child->_setUnserializedValues($childData);
                }
                
                $this->_collection[$key] = $child;  
            }
        }
    }
    
    
// Collection
    public function clear() {
        $this->_value = null;
        return parent::clear();
    }
    
    public function getReductiveIterator() {
        return new ReductiveMapIterator($this);
    }
    
    
// Clone
    public function __clone() {
        foreach($this->_collection as $key => $child) {
            $this->_collection[$key] = clone $child;
        }
        
        return $this;
    }
    
    
// Access
    public function getNestedChild($parts, $separator='.') {
        if(!is_array($parts)) {
            $parts = explode($separator, $parts);
        }
        
        $node = $this;
        
        while(null !== ($part = array_shift($parts))) {
            if(!strlen($part)) {
                if(!empty($node->_collection)) {
                    $part = max(array_keys($node->_collection)) + 1;
                } else {
                    $part = 0;
                }
            }
            
            $node = $node->{$part};
        }
        
        return $node;
    }
    
    public function getKeys() {
        return array_keys($this->_collection);
    }
    
    public function contains($value, $includeChildren=false) {
        foreach($this->_collection as $child) {
            if($child->_value == $value
            || ($includeChildren && $child->contains($value, true))) {
                return true;
            }
        }
        
        return false;
    }
    
    
    
    public function __set($key, $value) {
        $class = get_class($this);
        $this->_collection[$key] = new $class($value);
        return $this;
    }
    
    public function __get($key) {
        if(!array_key_exists($key, $this->_collection)) {
            $class = get_class($this);
            $this->_collection[$key] = new $class();
        }
        
        return $this->_collection[$key];
    }
    
    public function __isset($key) {
        return array_key_exists($key, $this->_collection);
    }
    
    public function __unset($key) {
        unset($this->_collection[$key]);
        return $this;
    }
    
    
    
    public function set($key, $value) {
        $this->__get($key)->setValue($value);
        return $this;
    }
    
    public function get($key, $default=null) {
        return $this->__get($key)->getValue($default);
    }
    
    public function has($key) {
        return array_key_exists($key, $this->_collection) 
            && $this->_collection[$key]->hasValue();
    }
    
    public function remove($key) {
        unset($this->_collection[$key]);
        return $this;
    }
    
    public function offsetSet($key, $value) {
        return $this->__set($key, $value);
    }
    
    public function clearKeys() {
        $this->_collection = array_values($this->_collection);
        return $this;
    }
    
    
// Shiftable
    public function extract() {
        return $this->shift();
    }
    
    public function insert($value) {
        $class = get_class($this);
        
        foreach(func_get_args() as $arg) {
            $this->_collection[] = new $class($arg); 
        }
        
        return $this;
    }
    
    public function pop() {
        return array_pop($this->_collection);
    }
    
    public function push($value) {
        $class = get_class($this);
        
        foreach(func_get_args() as $arg) {
            $this->_collection[] = new $class($arg); 
        }
        
        return $this;
    }
    
    public function shift() {
        return array_shift($this->_collection);
    }
    
    public function unshift($value) {
        $class = get_class($this);
        
        for($i = func_num_args() - 1; $i >= 0; $i--) {
            array_unshift($this->_collection, new $class(func_get_arg($i)) );
        }
        
        return $this;
    }
    
    
    
// Value container
    public function setValue($value) {
        $this->_value = $value;
        return $this;
    }
    
    public function getValue($default=null) {
        if($this->_value === null) {
            return $default;
        }
        
        return $this->_value;
    }
    
    public function hasValue() {
        return $this->_value !== null;
    }
    
    public function getStringValue($default='') {
        return (string)($this->_value === null ? $default : $this->_value);
    }
    
    
// String provider
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return (string)$this->_value;
        }
    }
    
    public function toString() {
        return $this->getStringValue();
    }
    
    public function toArrayDelimitedString($setDelimiter='&', $valueDelimiter='=') {
        $output = array();
        
        foreach($this->_toUrlEncodedArrayDelimitedSet() as $key => $value) {
            if(!empty($value)) {
                $output[] = $key.$valueDelimiter.rawurlencode($value);
            } else {
                $output[] = $key;
            }
        }
        
        return implode($setDelimiter, $output);
    }
    
    
// Array provider   
    public function toArray() {
        $output = array();
        
        foreach($this->_collection as $key => $child) {
            if($child->count()) {
                $output[$key] = $child->toArray();
            } else {
                $output[$key] = $child->getValue();
            }
        }
        
        return $output;
    }
    
    protected function _toArrayDelimitedSet($prefix=null) {
        $output = array();
        
        if($prefix 
        && ($this->_value !== null || empty($this->_collection))) {
            $output[$prefix] = $this->getValue();
        }
        
        foreach($this as $key => $child) {
            if($prefix) {
                $key = $prefix.'['.$key.']';
            }
            
            $output = array_merge($output, $child->_toArrayDelimitedSet($key));
        }
        
        return $output;
    }
    
    public function _toUrlEncodedArrayDelimitedSet($prefix=null) {
        $output = array();
        
        if($prefix 
        && ($this->_value !== null || empty($this->_collection))) {
            $output[$prefix] = $this->getValue();
        }
        
        foreach($this as $key => $child) {
            if($prefix) {
                $key = $prefix.'['.rawurlencode($key).']';
            }
            
            $output = array_merge($output, $child->_toUrlEncodedArrayDelimitedSet($key));
        }
        
        return $output;
    }
    
    
// Dump
    public function getDumpProperties() {
        $children = array();
        
        foreach($this->_collection as $key => $child) {
            if($child instanceof self && empty($child->_collection)) {
                $children[$key] = $child->_value;
            } else {
                $children[$key] = $child;
            }
        }
        
        if(empty($children)) {
            return $this->_value;
        }
        
        if(!empty($this->_value)) {
            array_unshift($children, new core\debug\dumper\Property('value', $this->_value, 'protected'));
        }
        
        return $children;
    }
}
