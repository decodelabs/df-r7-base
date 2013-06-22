<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Reference implements IReferenceNode {
    
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

    public function isArray() {
        return $this->_type == 'array';
    }
    
    public function getDumpId() {
        return $this->_dumpId;
    }

    public function getDataValue(IInspector $inspector) {
        return $this->toString();
    }
    
    public function toString() {
        return $this->_type.'(&'.$this->_dumpId.')';
    }
}
