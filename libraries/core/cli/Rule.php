<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

class Rule implements IRule {
    
    protected $_defaultName;
    protected $_names = [];
    protected $_valueRequired = false;
    protected $_canHaveValue = true;
    protected $_isRequired = false;
    protected $_defaultValue = null;
    protected $_valueType;
    protected $_description = null;

    public function __construct($names, $valueRequired=false, $valueType='s') {
        $this->setNames($names);
        $this->requiresValue((bool)$valueRequired);
        $this->setValueType($valueType);
    }

    public function setNames($names) {
        if(!is_array($names)) {
            $names = explode('|', $names);
        }

        $this->_names = $names;

        foreach($names as $name) {
            if(strlen($name) == 1) {
                if(!$this->_defaultName) {
                    $this->_defaultName = $name;
                }
            } else {
                $this->_defaultName = $name;
                break;
            }
        }
    }

    public function getName() {
        return $this->_defaultName;
    }

    public function getNames() {
        return $this->_names;
    }

    public function getFlags() {
        $output = [];

        foreach($this->_names as $name) {
            $output[] = (strlen($name) == 1 ? '-' : '--').$name;
        }

        return $output;
    }
    

    public function requiresValue($flag=null) {
        if($flag !== null) {
            $this->_valueRequired = (bool)$flag;

            if($this->_valueRequired) {
                $this->_canHaveValue = true;
            }

            return $this;
        }

        return $this->_valueRequired;
    }

    public function canHaveValue($flag=null) {
        if($flag !== null) {
            $this->_canHaveValue = (bool)$flag;

            if(!$this->_canHaveValue) {
                $this->_valueRequired = false;
            }

            return $this;
        }

        return $this->_canHaveValue;
    }

    public function isRequired($flag=null) {
        if($flag !== null) {
            $this->_isRequired = (bool)$flag;
            return $this;
        }

        return $this->_valueRequired && $this->_isRequired;
    }

    public function setDefaultValue($value) {
        $this->_defaultValue = $value;
        return $this;
    }

    public function getDefaultValue() {
        return $this->_defaultValue;
    }

    public function setValueType($type) {
        $this->_valueType = ValueType::factory($type);
        return $this;
    }

    public function getValueType() {
        return $this->_valueType;
    }

    public function setDescription($description) {
        $this->_description = $description;
        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }
}