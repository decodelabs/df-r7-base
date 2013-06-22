<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Immutable implements IImmutableNode {
    
    use core\TStringProvider;
    
    protected $_value;
    
    public function __construct($value) {
        $this->_value = $value;
    }
    
    public function isNull() {
        return $this->_value === null;
    }
    
    public function isBoolean() {
        return $this->_value !== null;
    }

    public function getType() {
        if($this->_value === null) {
            return 'null';
        } else {
            return 'boolean';
        }
    }
    
    public function getValue() {
        return $this->_value;
    }

    public function getDataValue(IInspector $inspector) {
        return $this->_value;
    }
    
    public function toString() {
        if($this->_value === null) {
            return 'null';
        }
        
        if($this->_value === true) {
            return 'true';
        }
        
        if($this->_value === false) {
            return 'false';
        }
    }
}
