<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class HeaderMap implements IMappedCollection, core\IStringProvider, \Iterator, core\IDumpable {
    
    use core\TStringProvider;
    use TArrayCollection;
    use TValueMapArrayAccess;
    
    
    public function __construct($input=null) {
        if($input !== null) {
            $this->import($input);
        }
    }
    
// Collection
    public function import($input) {
        if($input instanceof core\IArrayProvider) {
            $input = $input->toArray();
        }
        
        if(is_array($input)) {
            foreach($input as $key => $value) {
                $this->set($key, $value);
            }
        }
        
        return $this;
    }
    
    
// Access
    public function set($key, $value=null) {
        if(empty($key)) {
            throw new InvalidArgumentException('Invalid header input');
        }

        $key = $this->normalizeKey($key);
        
        if(is_array($value)) {
            $this->_collection[$key] = array();
            
            foreach($value as $k => $val) {
                $this->add($key, $val);
            }
            
            return $this;
        }
        
        if(isset($value)) {
            $this->_collection[$key] = $value;
        } else {
            unset($this->_collection[$key]);
        }

        return $this;
    }
    
    public function add($key, $value) {
        if(empty($key) || (isset($value) && !is_scalar($value))) {
            throw new InvalidArgumentException('Invalid header input');
        }

        $key = $this->normalizeKey($key);
        
        if($value === null) {
            return $this;
        }
        
        if(!isset($this->_collection[$key])) {
            $this->_collection[$key] = $value;
            return $this;    
        }
        
        if(!is_array($this->_collection[$key])) {
            $this->_collection[$key] = array($this->_collection[$key]);
        }
        
        $this->_collection[$key][] = $value;
        
        return $this;
    }
    
    public function append($key, $value) {
        if(empty($key) || (isset($value) && !is_scalar($value))) {
            throw new InvalidArgumentException('Invalid header input');
        }

        $key = $this->normalizeKey($key);
        
        if($value === null) {
            return $this;
        }
        
        if(!isset($this->_collection[$key])) {
            $this->_collection[$key] = '';    
        }
        
        if(is_array($this->_collection[$key])) {
            end($this->_collection[$key]);
            $lastKey = key($this->_collection[$key]);
            $this->_collection[$key][$lastKey] .= $value;
        } else {
            $this->_collection[$key] .= $value;
        }
        
        return $this;
    }

    public function get($key, $default=null) {
        $key = $this->normalizeKey($key);
        
        if(isset($this->_collection[$key])) {
            return $this->_collection[$key];
        }
        
        return $default;
    }

    public function setNamedValue($key, $name, $keyValue) {
        $value = $this->get($key);

        if($value === null) {
            return $this;
        }


        $new = preg_replace('/'.preg_quote($name).'="(.*)"/i', $name.'="'.$keyValue.'"', $value);

        if($new === $value) {
            if(false === strpos($value, ';')) {
                $value .= ';';
            }

            $value .= ' '.$name.'="'.$keyValue.'"';
        } else {
            $value = $new;
        }

        return $this->set($key, $value);
    }

    public function getNamedValue($key, $name, $default=null) {
        $value = $this->get($key);

        if($value === null) {
            return $default;
        }

        if(!preg_match('/\W*'.preg_quote($name).'="(.*)"/i', $value, $matches)) {
            return $default;
        }

        return $matches[1];
    }

    public function hasNamedValue($key, $name) {
        $value = $this->get($key);

        if($value === null) {
            return false;
        }

        return (bool)preg_match('/\W*'.preg_quote($name).'="(.*)"/i', $value);
    }

    public function has($key, $value=null) {
        $key = $this->normalizeKey($key);
        
        if(!isset($this->_collection[$key])) {
            return false;
        }
        
        if($value === null) {
            return true;
        }
        
        $comp = $this->_collection[$key];
        
        if(!is_array($comp)) {
            $comp = array($comp);
        }
        
        $value = strtolower($value);
        
        foreach($comp as $compVal) {
            $compVal = strtolower($compVal);
            
            if($compVal == $value) {
                return true;
            }
        }
        
        return false;
    }
    
    public function remove($key) {
        unset($this->_collection[$this->normalizeKey($key)]);
        return $this;
    }

    public static function normalizeKey($key) {
        return str_replace(
            ' ', '-', 
            ucwords(strtolower(
                str_replace(array('-', '_'), ' ', $key)
            ))
        );
    }
    
    
// Strings
    public function toString() {
        return implode("\r\n", $this->getLines());
    }
    
    public function getLines() {
        $output = array();
        
        foreach($this->_collection as $key => $value) {
            if(is_array($value)) {
                foreach($value as $v) {
                    $output[] = $key.': '.$this->_formatValue($key, $v); 
                }
            } else {
                $output[] = $key.': '.$this->_formatValue($key, $value); 
            }
        }
        
        return $output;
    }
    
    protected function _formatValue($key, $value) {
        if($value instanceof core\time\IDate) {
            return $value->toTimeZone('GMT')->format('D, d M Y H:i:s \G\M\T');
        }
        
        return (string)$value;
    }
    
    
    
// Iterator
    public function current() {
        $output = current($this->_collection);
        
        if(is_array($output)) {
            $output = current($output);
        }
        
        return $output;
    }
    
    public function next() {
        $key = key($this->_collection);
        
        if(is_array($this->_collection[$key])) {
            $output = next($this->_collection[$key]);
            
            if(key($this->_collection[$key]) !== null) {
                return $output;
            }
        }
        
        return next($this->_collection);
    }
    
    public function key() {
        return key($this->_collection);
    }
    
    public function rewind() {
        foreach($this->_collection as $key => &$value) {
            if(is_array($value)) {
                reset($value);
            }
        }
        
        return reset($this->_collection);
    }
    
    public function valid() {
        return key($this->_collection) !== null;
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = array();
        
        foreach($this->_collection as $key => $value) {
            if(is_array($value)) {
                foreach($value as $val) {
                    $output[] = new core\debug\dumper\Property($key, $val);
                }
            } else {
                $output[] = new core\debug\dumper\Property($key, $value);
            }
        }
        
        return $output;
    }
}
