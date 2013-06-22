<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class String implements IStringNode {
    
    use core\TStringProvider;
    
    protected $_string;
    
    public function __construct($string) {
        $this->_string = $string;
    }
    
    public function getValue() {
        return $this->_string;
    }
    
    public function getDataValue(IInspector $inspector) {
        return $this->_string;
    }

    public function toString() {
        return '"'.$this->_string.'"';
    }
}
