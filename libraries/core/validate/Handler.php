<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate;

use df;
use df\core;

class Handler implements IHandler {
    
    protected $_values = array();
    protected $_fields = array();
    protected $_isValid = null;
    protected $_shouldSanitize = false;
    protected $_currentData = null;
    
    public function addField($name, $type, array $options=null) {
        $field = core\validate\field\Base::factory($this, $type, $name);
        $field->shouldSanitize($this->_shouldSanitize);
        
        $this->_fields[$field->getName()] = $field;
        
        if($options !== null) {
            $field->applyOptions($options);
            return $this;
        } else {
            return $field;
        }
    }
    
    public function getField($name) {
        if(isset($this->_fields[$name])) {
            return $this->_fields[$name];
        }
    }
    
    public function getFields() {
        return $this->_fields;
    }
    
    public function shouldSanitize($flag=null) {
        if($flag !== null) {
            $this->_shouldSanitize = (bool)$flag;
            return $this;
        }
       
        return $this->_shouldSanitize;
    }
    
    public function isValid() {
        if($this->_isValid === null) {
            throw new RuntimeException(
                'This validator has not been run yet'
            );
        }
        
        return (bool)$this->_isValid;
    }
    
    public function getValues() {
        if($this->_isValid === null) {
            throw new RuntimeException(
                'This validator has not been run yet'
            );
        }
        
        return $this->_values;
    }
    
    public function getValue($name) {
        if($this->_isValid === null) {
            throw new RuntimeException(
                'This validator has not been run yet'
            );
        }
        
        if(isset($this->_values[$name])) {
            return $this->_values[$name];
        }
    }
    
    public function validate(core\collection\IInputTree $data) {
        $this->_isValid = true;
        $this->_values = array();
        $this->_currentData = $data;
        
        foreach($this->_fields as $name => $field) {
            $node = $data->{$name};
            $this->_values[$name] = $field->validate($node);
            
            if(!$node->isValid()) {
                $this->_isValid = false;
            }
        }
        
        $this->_currentData = null;
        return $this;
    }
    
    public function getCurrentData() {
        return $this->_currentData;
    }
    
    public function applyTo(&$record) {
        if(!is_array($record) && !$record instanceof \ArrayAccess) {
            throw new RuntimeException(
                'Target record does not implement ArrayAccess'
            );
        }

        if($this->_isValid) {
            foreach($this->_values as $key => $value) {
                $this->_fields[$key]->applyValueTo($record, $value);
            }
        }
        
        return $this;
    }
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        $translator = core\i18n\translate\Handler::factory('arch/Context', $locale);
        return $translator->_($phrase, $data, $plural);
    }
}
