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
    protected $_dumpId;
    
    public function __construct($type, $dumpId) {
        $this->_type = $type;
        $this->_dumpId = $dumpId;
    }
    
    public function getType() {
        return $this->_type;
    }
    
    public function getDumpId() {
        return $this->_dumpId;
    }
    
    public function toString() {
        return $this->_type.'(&'.$this->_id.')';
    }
}
