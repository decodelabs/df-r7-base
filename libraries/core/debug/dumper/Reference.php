<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Reference implements core\debug\IDump {
    
    use core\TStringProvider;
    
    protected $_type;
    protected $_id;
    
    public function __construct($type, $id) {
        $this->_type = $type;
        $this->_id = $id;
    }
    
    public function toString() {
        return $this->_type.'(&'.$this->_id.')';
    }
}
