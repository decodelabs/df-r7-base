<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record;

use df;
use df\core;
use df\opal;
    
class LazyLoadValueContainer implements IPreparedValueContainer {

    protected $_value;
    protected $_isLoaded = false;
    protected $_loader;

    public function __construct($initValue, Callable $loader) {
        $this->_value = $initValue;
        $this->_loader = $loader;
    }

    public function isPrepared() {
        return $this->_isLoaded;
    }
    
    public function prepareValue(opal\query\record\IRecord $record, $fieldName) {
        $this->_value = call_user_func_array($this->_loader, [$this->_value, $record, $fieldName]);
        $this->_isLoaded = true;
        return $this;
    }
    
    public function prepareToSetValue(opal\query\record\IRecord $record, $fieldName) {
        return $this;
    }
    

    public function setValue($value) {
        $this->_value = $value;
        $this->_isLoaded = true;
        return $this;
    }
    
    public function getValue($default=null) {
        if($this->_isLoaded) {
            return $this->_value;
        }

        core\stub($this->_value, $default);
    }
    
    public function getValueForStorage() {
        return $this->_value;
    }
    
    
    public function duplicateForChangeList() {
        return clone $this;
    }
    
    public function eq($value) {
        return null;


        core\dump($this->_value, $value);
        if(!$this->_isLoaded) {
            return false;
        }


        return $this->_value == $value;
    }

    public function getDumpValue() {
        return $this->_value;
    }
}