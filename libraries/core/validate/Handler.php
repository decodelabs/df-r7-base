<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate;

use df;
use df\core;

class Handler implements IHandler {
    
    use core\lang\TChainable;

    protected $_values = [];
    protected $_fields = [];
    protected $_targetField = null;
    protected $_requireGroups = [];
    protected $_isValid = null;
    protected $_shouldSanitizeAll = true;

    public $data = null;

    public function addField($name, $type) {
        $this->newField($name, $type);
        return $this;
    }

    public function addRequiredField($name, $type) {
        $this->newField($name, $type)->isRequired(true);
        return $this;
    }

    public function newField($name, $type) {
        $this->endField();
        $field = core\validate\field\Base::factory($this, $type, $name);
        $field->shouldSanitize($this->_shouldSanitizeAll);
        
        $this->_fields[$field->getName()] = $field;
        $this->_targetField = $field;

        return $field;
    }

    public function newRequiredField($name, $type) {
        return $this->newField($name, $type)->isRequired(true);
    }

    public function getTargetField() {
        return $this->_targetField;
    }

    public function endField() {
        $this->_targetField = null;
        return $this;
    }
    
    public function __call($method, array $args) {
        if(!$this->_targetField) {
            throw new RuntimeException(
                'There is no active target field to apply method '.$method.' to'
            );
        }

        if(!method_exists($this->_targetField, $method)) {
            throw new BadMethodCallException(
                'Target field '.$this->_targetField->getName().' does not have method '.$method
            );
        }

        $output = call_user_func_array([$this->_targetField, $method], $args);

        if($output === $this->_targetField) {
            return $this;
        }

        return $output;
    }


    public function getField($name) {
        if(isset($this->_fields[$name])) {
            return $this->_fields[$name];
        }
    }
    
    public function getFields() {
        return $this->_fields;
    }
    
    public function shouldSanitizeAll($flag=null) {
        if($flag !== null) {
            $this->_shouldSanitizeAll = (bool)$flag;
            return $this;
        }
       
        return $this->_shouldSanitizeAll;
    }
    
    public function isValid() {
        if($this->_isValid === null) {
            return true;
        }

        if(!$this->_isValid) {
            return false;
        }

        return $this->data->isValid();
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

    public function setValue($name, $value) {
        if($this->_isValid === null) {
            throw new RuntimeException(
                'This validator has not been run yet'
            );
        }

        $this->_values[$name] = $value;
        return $this;
    }

    public function offsetSet($offset, $value) {
        //throw new BadMethodCallException('Validator values cannot be set via array access');
        return $this->setValue($offset, $value);
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
        $this->endField();

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
            if($fields === true) {
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

        foreach($fields as $key) {
            if(!$this->data->{$key}->isValid()) {
                continue;
            }

            if(array_key_exists($key, $this->_values)) {
                $this->_fields[$key]->applyValueTo($record, $this->_values[$key]);
            }
        }
        
        return $this;
    }
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        $translator = core\i18n\translate\Handler::factory('core/Validate', $locale);
        return $translator->_($phrase, $data, $plural);
    }
}
