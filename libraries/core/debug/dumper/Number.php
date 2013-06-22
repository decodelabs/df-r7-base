<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Number implements INumberNode {
    
    use core\TStringProvider;
    
    protected $_number;
    
    public function __construct($number) {
        $this->_number = $number;
    }
    
    public function getValue() {
        return $this->_number;
    }
    
    public function isFloat() {
        return is_float($this->_number);
    }
    
    public function getDataValue() {
        return $this->_number;
    }

    public function toString() {
        $output = (string)$this->_number;
        
        if(is_float($this->_number)) {
            $output .= 'f';
        }
        
        return $output;
    }
}
