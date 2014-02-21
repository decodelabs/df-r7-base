<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate;

use df;
use df\core;

class Handler implements IHandler {
    
    use core\TChainable;

    protected $_values = [];
    protected $_fields = [];
    protected $_requireGroups = [];
    protected $_isValid = null;
    protected $_shouldSanitize = true;

    public $data = null;

    public function addField($name, $type) {
        $field = core\validate\field\Base::factory($this, $type, $name);
        $field->shouldSanitize($this->_shouldSanitize);
        
        $this->_fields[$field->getName()] = $field;

        return $field;
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

    public function setRequireGroupFulfilled($name) {
        $this->_requireGroups[$name] = true;
        return $this;
    }

    public function setRequireGroupUnfulfilled($name, $field) {
        if(isset($this->_requireGroups[$name]) && $this->_requireGroups[$name] === true) {
            return $this;
        }

        $this->_requireGroups[$name][] = $field;
        return $this;
    }

    public function checkRequireGroup($name) {
        if(isset($this->_requireGroups[$name])) {
            return $this->_requireGroups[$name] === true;
        }

        return false;
    }
    

// Io
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

    public function offsetSet($offset, $value) {
        throw new BadMethodCallException('Validator values cannot be set via array access');
    }

    public function offsetGet($offset) {
        return $this->getValue($offset);
    }

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->_values);
    }

    public function offsetUnset($offset) {
        throw new BadMethodCallException('Validator values cannot be set via array access');
    }
    


// Validate
    public function validate($data) {
        if(!$data instanceof core\collection\IInputTree) {
            $data = core\collection\InputTree::factory($data);
        }

        $this->_isValid = true;
        $this->_values = [];
        $this->_requireGroups = [];
        $this->data = $data;
        
        foreach($this->_fields as $name => $field) {
            $node = $data->{$name};
            $this->_values[$name] = $field->validate($node);
            
            if(!$node->isValid()) {
                $this->_isValid = false;
            }
        }

        foreach($this->_requireGroups as $name => $fields) {
            if($field === true) {
                continue;
            }

            foreach($fields as $field) {
                $data->{$field}->addError('required', $this->_('You must enter a value in one of these fields'));
                $this->_isValid = false;
            }
        }
        
        return $this;
    }
    
    public function getCurrentData() {
        return $this->data;
    }
    
    public function applyTo(&$record, array $fields=null) {
        if(!is_array($record) && !$record instanceof \ArrayAccess) {
            throw new RuntimeException(
                'Target record does not implement ArrayAccess'
            );
        }

        if(empty($fields)) {
            $fields = array_keys($this->_values);
        }

        if($this->_isValid) {
            foreach($fields as $key) {
                if(array_key_exists($key, $this->_values)) {
                    $this->_fields[$key]->applyValueTo($record, $this->_values[$key]);
                }
            }
        }
        
        return $this;
    }
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        $translator = core\i18n\translate\Handler::factory('core/Validate', $locale);
        return $translator->_($phrase, $data, $plural);
    }
}
