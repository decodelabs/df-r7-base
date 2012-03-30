<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\constraint;

use df;
use df\core;
use df\acid;

class Boolean extends Base {
    
    protected $_expected;
    
    public function __construct($expected) {
        $this->_expected = (bool)$expected;
    }
    
    public function evaluate($value) {
        return $value === $this->_expected;
    }
    
    public function getExpectation() {
        return $this->_expected;
    }
    
    public function getFailureDescription($value) {
        $output = gettype($value).' is not boolean ';
        
        if($this->_expected) {
            $output .= 'true';
        } else {
            $output .= 'false';
        }
        
        return $output;
    }
    
    public function getNegatedFailureDescription($value) {
        $output = gettype($value).' is boolean ';
        
        if($this->_expected) {
            $output .= 'true';
        } else {
            $output .= 'false';
        }
        
        return $output;
    }
}
