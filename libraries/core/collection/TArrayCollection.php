<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;


trait TArrayCollection {
    
    protected $_collection = array();
    
    public function isEmpty() {
        return empty($this->_collection);
    }
    
    public function clear() {
        $this->_collection = array();
        return $this;
    }
    
    public function extract() {
        return array_shift($this->_collection);
    }
    
    public function extractList($count) {
        $output = array();
        
        for($i = 0; $i < (int)$count; $i++) {
            $output[] = $this->extract();
        }
        
        return $output;
    }
    
    public function toArray() {
        return $this->_collection;
    }
    
    public function count() {
        return count($this->_collection);
    }
    
    public function getIterator() {
        return new \ArrayIterator($this->_collection);
    }
}



// Sortable
trait TSortableArrayCollection {
    
    public function sortByKey() {
        ksort($this->_collection);
        return $this;
    }
    
    public function reverseSortByKey() {
        krsort($this->_collection);
        return $this;
    }
    
    public function reverse() {
        $this->_collection = array_reverse($this->_collection);
        return $this;
    }
}

trait TSortableScalarArrayCollection {
    
    use TSortableArrayCollection;
    
    public function sortByValue() {
        asort($this->_collection);
        return $this;
    }
    
    public function reverseSortByValue() {
        arsort($this->_collection);
        return $this;
    }
}

trait TSortableValueContainerArrayCollection {
    
    use TSortableArrayCollection;
    
    public function sortByValue() {
        uasort($this->_collection, function(core\IValueContainer $a, core\IValueContainer $b) {
            $a = $a->getValue();
            $b = $b->getValue();
            
            if($a === $b) {
                return 0;
            }
            
            return $a < $b ? -1 : 1;
        });
    }
    
    public function reverseSortByValue() {
        uasort($this->_collection, function(core\IValueContainer $b, core\IValueContainer $a) {
            $a = $a->getValue();
            $b = $b->getValue();
            
            if($a === $b) {
                return 0;
            }
            
            return $a < $b ? -1 : 1;
        });
    }
}


// Value map
trait TValueMapArrayCollection {
        
    public function offsetSet($index, $value) {
        return $this->set($index, $value);
    }
    
    public function offsetGet($index) {
        return $this->get($index);
    }
    
    public function offsetExists($index) {
        return $this->has($index);
    }
    
    public function offsetUnset($index) {
        return $this->remove($index);
    }
}

trait TAssociativeValueMapArrayCollection {
    
    use TValueMapArrayCollection;
    
    public function set($key, $value) {
        $this->_collection[(string)$key] = $value;
        return $this; 
    }
    
    public function get($key, $default=null) {
        $key = (string)$key;
        
        if(array_key_exists($key, $this->_collection)) {
            return $this->_collection[$key];
        }
        
        return $default;
    }
    
    public function has($key) {
        return array_key_exists((string)$key, $this->_collection);
    }
    
    public function remove($key) {
        unset($this->_collection[(string)$key]);
        return $this;
    }
    
    public function clearKeys() {
        $this->_collection = array_values($this->_collection);
        return $this;
    }
}



trait TIndexedValueMapArrayCollection {
    
    use TValueMapArrayCollection;
    
    public function set($index, $value) {
        $count = count($this->_collection);
        
        if($index === null) {
            $index = $count;
        }
        
        $index = (int)$index;
        
        if($index < 0) {
            $index += $count;
            
            if($count == 0 && $index == -1) {
                $index = 0;
            }
            
            if($index < 0) {
                throw new OutOfBoundsException(
                    'Trying to set a negative index outside of current bounds'
                );
            }
        }
        
        if($index > $count) {
            $index = $count;
        }
        
        $this->_collection[$index] = $value;
        return $this;
    }
    
    public function put($index, $value) {
        $count = count($this->_collection);
        $index = (int)$index;
        
        if($index < 0) {
            $index += $count;
            
            if($count == 0 && $index == -1) {
                $index = 0;
            }
            
            if($index < 0) {
                throw new OutOfBoundsException(
                    'Trying to set a negative index outside of current bounds'
                );
            }
        }
        
        $addVals = null;
        
        if($index < $count) {
            $addVals = array_splice($this->_collection, $index);
            $count = $index;
        }
        
        $this->_collection[] = $value;
        
        if($addVals !== null) {
            $this->_collection = array_merge($this->_collection, $addVals);
        }
        
        return $this;
    }
    
    public function get($index, $default=null) {
        $index = (int)$index;
        
        if($index < 0) {
            $index += count($this->_collection);
            
            if($index < 0) {
                return $default;
            }
        }
        
        if(array_key_exists($index, $this->_collection)) {
            return $this->_collection[$index];
        }
        
        return $default;
    }
    
    public function has($index) {
        $index = (int)$index;
        
        if($index < 0) {
            $index += count($this->_collection);
            
            if($index < 0) {
                return false;
            }
        }
        
        return array_key_exists($index, $this->_collection);
    }
    
    public function remove($index) {
        $index = (int)$index;
        
        if($index < 0) {
            $index += count($this->_collection);
            
            if($index < 0) {
                return $this;
            }
        }
        
        unset($this->_collection[$index]);
        $this->_collection = array_values($this->_collection);
        return $this;
    }
}


trait TIndexedProcessedValueMapArrayCollection {
    
    use TIndexedValueMapArrayCollection;
    
    public function set($index, $value) {
        $values = $this->_expandInput($value);
        
        if(!$valCount = count($values)) {
            return $this->remove($index);
        }
        
        $count = count($this->_collection);
        $index = (int)$index;
        
        if($index < 0) {
            $index += $count;
            
            if($count == 0 && $index == -1) {
                $index = 0;
            }
            
            if($index < 0) {
                throw new OutOfBoundsException(
                    'Trying to set a negative index outside of current bounds'
                );
            }
        }
        
        if($index > $count + $valCount - 1) {
            $index = $count + $valCount - 1;
        }
        
        $boundCount = $index - $count;
        
        while($valCount > 0) {
            if($index >= $count) {
                // Outside of upper bounds, pad out by valCount
                $this->_collection[] = $values[$boundCount];
                unset($values[$boundCount]);
                $boundCount--;
            } else if($index < 0) {
                // Outside of lower bounds, unshift
                array_unshift($this->_collection, array_shift($values));
                $count++;
            } else if($index < $count) {
                // Within bounds, just set it
                $this->_collection[$index] = array_shift($values);
            }
            
            $valCount--;
            $index--;
        }
        
        $this->_onInsert();
        return $this;
    }

    public function put($index, $value) {
        $values = $this->_expandInput($value);
        
        if(!$valCount = count($values)) {
            return $this;
        }
        
        $count = count($this->_collection);
        $index = (int)$index;
        
        if($index < 0) {
            $index += $count;
            
            if($count == 0 && $index == -1) {
                $index = 0;
            }
            
            if($index < 0) {
                throw new OutOfBoundsException(
                    'Trying to set a negative index outside of current bounds'
                );
            }
        }
        
        $addVals = null;
        
        if($index < $count) {
            $addVals = array_splice($this->_collection, $index);
            $count = $index;
        }
        
        while($valCount > 0) {
            $this->_collection[] = array_pop($values);
            $valCount--;
        }
        
        if($addVals !== null) {
            $this->_collection = array_merge($this->_collection, $addVals);
        }
        
        $this->_onInsert();
        return $this;
    }

    abstract protected function _expandInput($input);
    abstract protected function _onInsert();
}


trait TUniqueSetArrayCollection {
    
    public function add($values) {
        if(!is_array($values)) {
            $values = func_get_args();
        }
        
        foreach($values as $part) {
            if(!in_array($part, $this->_collection, true)) {
                $this->_collection[] = $entry;
            }
        }
    }
    
    public function has($value) {
        return in_array($value, $this->_collection, true);
    }
    
    public function remove($values) {
        if(!is_array($values)) {
            $values = func_get_args();
        }
        
        foreach($this->_collection as $i => $setValue) {
            if(in_array($setValue, $values, true)) {
                unset($this->_collection[$i]);
            }
        }
        
        $this->_collection = array_values($this->_collection);
        return $this;
    }
    
    public function replace($current, $new) {
        foreach($this->_collection as $i => $setValue) {
            if($setValue === $current) {
                $this->_collection[$i] = $new;
                break;
            }
        }
        
        return $this;
    }
}


// Seekable
trait TSeekableArrayCollection {
    
    public function getCurrent() {
        if(false === ($output = current($this->_collection))) {
            return null;
        }
        
        return $output;
    }
    
    public function getFirst() {
        return reset($this->_collection);
    }
    
    public function getNext() {
        return next($this->_collection);
    }
    
    public function getPrev() {
        return prev($this->_collection);
    }
    
    public function getLast() {
        return end($this->_collection);
    }
    
    public function seekFirst() {
        reset($this->_collection);
        return $this;
    }
    
    public function seekNext() {
        next($this->_collection);
        return $this;
    }
    
    public function seekPrev() {
        prev($this->_collection);
        return $this;
    }
    
    public function seekLast() {
        end($this->_collection);
        return $this;
    }
    
    public function hasSeekEnded() {
        return key($this->_collection) === null;
    }
    
    public function getSeekPosition() {
        return key($this->_collection);
    }
}


trait TReverseSeekableArrayCollection {
    
    public function getCurrent() {
        if(false === ($output = current($this->_collection))) {
            return null;
        }
        
        return $output;
    }
    
    public function getFirst() {
        return end($this->_collection);
    }
    
    public function getNext() {
        return prev($this->_collection);
    }
    
    public function getPrev() {
        return next($this->_collection);
    }
    
    public function getLast() {
        return reset($this->_collection);
    }
    
    public function seekFirst() {
        end($this->_collection);
        return $this;
    }
    
    public function seekNext() {
        prev($this->_collection);
        return $this;
    }
    
    public function seekPrev() {
        next($this->_collection);
        return $this;
    }
    
    public function seekLast() {
        reset($this->_collection);
        return $this;
    }
    
    public function hasSeekEnded() {
        return key($this->_collection) === null;
    }
    
    public function getSeekPosition() {
        return key($this->_collection);
    }
}


// Shiftable
trait TShiftableArrayCollection {
    
    public function pop() {
        return array_pop($this->_collection);
    }
    
    public function push($value) {
        foreach(func_get_args() as $arg) {
            $this->_collection[] = $arg;
        }
        
        return $this;
    }
    
    public function shift() {
        return array_shift($this->_collection);
    }
    
    public function unshift($value) {
        for($i = func_num_args() - 1; $i >= 0; $i--) {
            array_unshift($this->_collection, func_get_arg($i));
        }
        
        return $this;
    }
}

trait TProcessedShiftableArrayCollection {
    
    public function pop() {
        return array_pop($this->_collection);
    }
    
    public function push($value) {
        foreach(array_reverse(func_get_args()) as $arg) {
            foreach(array_reverse($this->_expandInput($arg)) as $value) {
                array_push($this->_collection, $value);
            }
        }
        
        $this->_onInsert();
        return $this;
    }
    
    public function shift() {
        return array_shift($this->_collection);
    }
    
    public function unshift($value) {
        foreach(func_get_args() as $arg) {
            foreach($this->_expandInput($arg) as $value) {
                array_unshift($this->_collection, $value);
            }
        }
        
        $this->_onInsert();
        return $this;
    }
    
    abstract protected function _expandInput($input);
    abstract protected function _onInsert();
}
