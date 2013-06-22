<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\node;

use df;
use df\core;

class Dump implements core\log\IDumpNode {
    
    use core\debug\TLocationProvider;
    
    private static $_counter = 0;
    
    protected $_object;
    protected $_id;
    protected $_isDeep = false;
    protected $_isCritical = true;
    
    public function __construct(&$object, $deep=false, $critical=true, $file=null, $line=null) {
        $this->_object = &$object;
        $this->_id = ++self::$_counter;
        $this->_isDeep = (bool)$deep;
        $this->_isCritical = $critical;
        
        $this->_file = $file;
        $this->_line = $line;
    }
    
    public function getNodeTitle() {
        return 'Dump #'.$this->_id.' '.ucfirst(getType($this->_object));
    }
    
    public function getNodeType() {
        return 'dump';
    }
    
    public function isCritical() {
        return $this->_isCritical;
    }
    
    public function &getObject() {
        return $this->_object;
    }
    
    public function isDeep() {
        return $this->_isDeep;
    }
}
