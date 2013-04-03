<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class Set implements ISet, IAggregateIteratorCollection, core\IDumpable {
    
    use TArrayCollection;
    use TArrayCollection_Constructor;
    use TArrayCollection_UniqueSet;
    
    public function import($input) {
        if($input instanceof core\IArrayProvider) {
            $input = $input->toArray();
        }
        
        if(!is_array($input)) {
            $input = array($input);
        }
        
        foreach(array_unique($input) as $value) {
            $this->_collection[] = $value;
        }
        
        return $this;
    }
    
    public function getReductiveIterator() {
        return new ReductiveIndexIterator($this);
    }
    
// Dump
    public function getDumpProperties() {
        $output = array();
        
        foreach($this->_collection as $value) {
            $output[] = new core\debug\dumper\Property(null, $value);
        }
        
        return $output;
    }
}
