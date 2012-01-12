<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Immutable implements core\debug\IDump {
    
    use core\TStringProvider;
    
    protected $_value;
    
    public function __construct($value) {
        $this->_value = $value;
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
