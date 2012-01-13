<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class Map implements IMappedCollection, ISeekable, ISortable, IAggregateIteratorCollection, core\IDumpable {
    
    use TArrayCollection;
    use TSortableScalarArrayCollection;
    use TAssociativeValueMapArrayCollection;
    use TSeekableArrayCollection;
    
    public function __construct($input=null) {
        if($input !== null) {
            $this->import($input);
        }
    }
    
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
    
    public function getReductiveIterator() {
        return new ReductiveMapIterator($this);
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}
