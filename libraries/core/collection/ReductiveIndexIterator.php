<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class ReductiveIndexIterator implements \Iterator {
    
    protected $_collection;
    protected $_pos = 0;
    protected $_row;
    
    public function __construct(ICollection $collection) {
        $this->_collection = $collection;
    }
    
    public function current() {
        if($this->_row === null) {
            $this->_row = $this->_collection->extract();
        }
        
        return $this->_row;
    }
    
    public function next() {
        $this->_pos++;
        $this->_row = null;
    }
    
    public function key() {
        return $this->_pos;
    }
    
    public function valid() {
        return !$this->_collection->isEmpty();
    }
    
    public function rewind() {
        $this->_pos = 0;
        
        if($this->_collection instanceof ISeekable) {
            $this->_collection->seekFirst();
        }
    }
}