<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

abstract class Base implements core\validate\IField {
    
    protected $_name;
    protected $_recordName = null;
    protected $_isRequired = false;
    protected $_requireGroup = null;
    protected $_toggleField = null;
    protected $_shouldSanitize = true;
    protected $_customValidator = null;
    protected $_handler;
    
    
    public static function factory(core\validate\IHandler $handler, $type, $name) {
        $class = 'df\\core\\validate\\field\\'.ucfirst($type);
        
        if(!class_exists($class)) {
            throw new core\validate\RuntimeException(
                'Validator type '.ucfirst($type).' could not be found for field '.$name
            );
        }
        
        return new $class($handler, $name);
    }
    
    
    public function __construct(core\validate\IHandler $handler, $name) {
        $this->_handler = $handler;
        $this->_name = $name;
    }
    
    public function getName() {
        return $this->_name;
    }

    public function setRecordName($name) {
        $this->_recordName = $name;
        return $this;
    }

    public function getRecordName() {
        if($this->_recordName) {
            return $this->_recordName;
        } else {
            return $this->_name;
        }
    }
    
    public function getHandler() {
        return $this->_handler;
    }
    
    public function isRequired($flag=null) {
        if($flag !== null) {
            $this->_isRequired = (bool)$flag;
            return $this;
        }
        
        return $this->_isRequired;
    }

    public function setRequireGroup($name) {
        $this->_requireGroup = $name;
        return $this;
    }

    public function getRequireGroup() {
        return $this->_requireGroup;
    }
    
    public function setToggleField($name) {
        $this->_toggleField = $name;
        return $this;
    }

    public function getToggleField() {
        return $this->_toggleField;
    }

    public function shouldSanitize($flag=null) {
        if($flag !== null) {
            $this->_shouldSanitize = (bool)$flag;
            return $this;
        }
       
        return $this->_shouldSanitize;
    }
    
    
    public function setCustomValidator(Callable $validator) {
        $this->_customValidator = $validator;
        return $this;
    }
    
    public function getCustomValidator() {
        return $this->_customValidator;
    }
    
    
    public function end() {
        return $this->_handler;
    }
    
    public function applyValueTo(&$record, $value) {
        if(!is_array($record) && !$record instanceof \ArrayAccess) {
            throw new RuntimeException(
                'Target record does not implement ArrayAccess'
            );
        }
        
        $name = $this->getRecordName();

        //if($value !== null || !isset($record[$name])) {
            $record[$name] = $value;
        //}
        
        return $this;
    }
    
    protected function _checkRequired(core\collection\IInputTree $node, $value) {
        if($this->_shouldSanitize) {
            $node->setValue($value);
        }

        $required = $this->_isRequired;

        if($this->_toggleField) {
            if($field = $this->_handler->getField($this->_toggleField)) {
                $toggle = (bool)$this->_handler[$this->_toggleField];

                if(!$toggle) {
                    $node->setValue($value = null);
                }

                if($required) {
                    $required = $toggle;
                }
            }
        }

        if(!$length = mb_strlen($value)) {
            $value = null;
            
            if($required) {
                $node->addError('required', $this->_handler->_('This field cannot be empty'));
            }

            if($this->_requireGroup !== null && !$this->_handler->checkRequireGroup($this->_requireGroup)) {
                $this->_handler->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else if($this->_requireGroup !== null) {
            $this->_handler->setRequireGroupFulfilled($this->_requireGroup);
        }
        
        return $length;
    }
    
    protected function _finalize(core\collection\IInputTree $node, $value) {
        $value = $this->_applyCustomValidator($node, $value);
        
        if($this->_shouldSanitize) {
            $node->setValue($value);
        }
        
        return $value;
    }
    
    protected function _applyCustomValidator(core\collection\IInputTree $node, $value) {
        if(!$node->hasErrors() && $this->_customValidator) {
            call_user_func_array($this->_customValidator, [$node, $value, $this]);
        }
        
        return $value;
    }
}