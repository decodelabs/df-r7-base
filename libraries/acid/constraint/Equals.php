<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\constraint;

use df;
use df\core;
use df\acid;

class Equals extends Base {
    
    protected $_expected;
    
    public function __construct($expected) {
        $this->_expected = $expected;
    }
    
    public function evaluate($value) {
        // TODO: use comparator
        return $this->_expected == $value;
    }
    
    public function getExpectation() {
        return $this->_expected;
    }
    
    public function getFailureDescription($value) {
        return gettype($value).' does not equal expected '.gettype($this->_expected);
    }
    
    public function getNegatedFailureDescription($value) {
        return gettype($value).' equals expected '.gettype($this->_expected);
    }
}
