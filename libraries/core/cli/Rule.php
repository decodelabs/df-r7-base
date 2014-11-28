<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

class Rule implements IRule {
    
    protected $_shortName;
    protected $_longName;
    protected $_valueRequired = false;
    protected $_canHaveValue = true;
    protected $_isRequired = false;
    protected $_defaultValue = null;
    protected $_valueType;
    protected $_description = null;

    public function __construct($shortName, $longName=null, $valueRequired=false, $valueType='s') {
        $this->setNames($shortName, $longName);
        $this->requiresValue((bool)$valueRequired);
        $this->setValueType($valueType);
    }

    public function setNames($shortName, $longName) {
        return $this->setShortName($shortName)->setLongName($longName);
    }

    public function getName() {
        if($this->_shortName !== null) {
            return $this->_shortName;
        }

        return $this->_longName;
    }

    public function setShortName($name) {
        if(!strlen($name)) {
            $name = null;
        }

        $this->_shortName = $name;
        return $this;
    }

    public function getShortName() {
        return $this->_shortName;
    }

    public function hasShortName() {
        return $this->_shortName !== null;
    }

    public function setLongName($name) {
        if(!strlen($name)) {
            $name = null;
        }

        $this->_longName = $name;
        return $this;
    }

    public function getLongName() {
        return $this->_longName;
    }

    public function hasLongName() {
        return $this->_longName !== null;
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