<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class ReductiveMapIterator implements \Iterator {
    
    protected $_collection;
    protected $_key;
    protected $_row;
    
    public function __construct(ISeekable $collection) {
        $this->_collection = $collection;
    }
    
    public function current() {
        if($this->_row === null) {
            $this->_key = $this->_collection->getSeekPosition();
            $this->_row = $this->_collection->extract();
        }
        
        return $this->_row;
    }
    
    public function next() {
        $this->_row = null;
    }
    
    public function key() {
        return $this->_key;
    }
    
    public function valid() {
        return !$this->_collection->isEmpty();
    }
    
    public function rewind() {
        $this->_collection->seekFirst();
    }
}

