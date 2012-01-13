<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class Stack implements IStack, IAggregateIteratorCollection, core\IDumpable {

    use TArrayCollection;
    use TIndexedValueMapArrayCollection;
    use TReverseSeekableArrayCollection;
    use TShiftableArrayCollection;
    
    public function __construct($input=null) {
        if($input !== null) {
            $this->import($input);
        }
    }    
    
    public function import($input) {
        if($input instanceof core\IArrayProvider) {
            $input = $input->toArray();
        }
        
        if(!is_array($input)) {
            $input = array($input);
        }
        
        foreach($input as $value) {
            $this->_collection[] = $value;
        }
        
        return $this;
    }
    
    
// Iterator
    public function getIterator() {
        return new \ArrayIterator(array_reverse($this->_collection, true));
    }
    
    public function getReductiveIterator() {
        return new ReductiveReverseIndexIterator($this);
    }
    
    
// Shiftable
    public function extract() {
        return $this->pop();
    }
    
    public function insert($value) {
        foreach(func_get_args() as $arg) {
            $this->_collection[] = $arg;
        }
        
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}
