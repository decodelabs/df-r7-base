<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

class Rule implements IRule {

    protected $_defaultName = '';
    protected $_names = [];
    protected $_valueRequired = false;
    protected $_canHaveValue = true;
    protected $_isRequired = false;
    protected $_defaultValue = null;
    protected $_valueType;
    protected $_description = null;

    public function __construct($names, bool $valueRequired=false, $valueType='s') {
        $this->setNames($names);
        $this->requiresValue($valueRequired);
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

        return $this;
    }

    public function getName(): string {
        return $this->_defaultName;
    }

    public function getNames(): array {
        return $this->_names;
    }

    public function getFlags(): array {
        $output = [];

        foreach($this->_names as $name) {
            $output[] = (strlen($name) == 1 ? '-' : '--').$name;
        }

        return $output;
    }


    public function requiresValue(bool $flag=null) {
        if($flag !== null) {
            $this->_valueRequired = $flag;

            if($this->_valueRequired) {
                $this->_canHaveValue = true;
            }

            return $this;
        }

        return $this->_valueRequired;
    }

    public function canHaveValue(bool $flag=null) {
        if($flag !== null) {
            $this->_canHaveValue = $flag;

            if(!$this->_canHaveValue) {
                $this->_valueRequired = false;
            }

            return $this;
        }

        return $this->_canHaveValue;
    }

    public function isRequired(bool $flag=null) {
        if($flag !== null) {
            $this->_isRequired = $flag;
            return $this;
        }

        return $this->_valueRequired && $this->_isRequired;
    }

    public function setDefaultValue(?string $value) {
        $this->_defaultValue = $value;
        return $this;
    }

    public function getDefaultValue(): ?string {
        return $this->_defaultValue;
    }

    public function setValueType($type) {
        $this->_valueType = ValueType::factory($type);
        return $this;
    }

    public function getValueType(): ValueType {
        return $this->_valueType;
    }

    public function setDescription(?string $description) {
        $this->_description = $description;
        return $this;
    }

    public function getDescription(): ?string {
        return $this->_description;
    }
}
