<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\node;

use df;
use df\core;

class Dump implements core\debug\IDumpNode {
    
    use core\debug\TLocationProvider;
    
    private static $_counter = 0;
    
    protected $_object;
    protected $_id;
    protected $_deep = false;
    
    public function __construct(&$object, $deep=false, $file=null, $line=null) {
        $this->_object = &$object;
        $this->_id = ++self::$_counter;
        $this->_deep = (bool)$deep;
        
        $this->_file = $file;
        $this->_line = $line;
    }
    
    public function getNodeTitle() {
        return '#'.$this->_id.' - '.ucfirst(getType($this->_object));
    }
    
    public function getNodeType() {
        return 'dump';
    }
    
    public function isCritical() {
        return true;
    }
    
    public function &getObject() {
        return $this->_object;
    }
    
    public function isDeep() {
        return $this->_deep;
    }
}
