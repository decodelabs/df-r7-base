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
    protected $_isRequired = false;
    protected $_shouldSanitize = false;
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
        
        $record[$this->_name] = $value;
        return $this;
    }
    
    protected function _checkRequired(core\collection\IInputTree $node, $value) {
        if(!$length = strlen($value)) {
            $value = null;
            
            if($this->_shouldSanitize) {
                $node->setValue($value);
            }
            
            if($this->_isRequired) {
                $node->addError('required', $this->_handler->_('Please fill in this field'));
            }
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