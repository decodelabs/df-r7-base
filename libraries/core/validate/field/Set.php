<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
    
class Set extends Base implements core\validate\IEnumField {

    use core\validate\TSanitizingField;

    protected $_options = [];

    public function setOptions(array $options) {
        $this->_options = $options;
        return $this;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->toArray();
        $value = (array)$this->_sanitizeValue($value);
        $required = $this->_isRequired;

        if($this->_toggleField) {
            if($field = $this->_handler->getField($this->_toggleField)) {
                $toggle = (bool)$this->_handler[$this->_toggleField];

                if(!$toggle) {
                    $node->setValue($value = []);
                }

                if($required) {
                    $required = $toggle;
                }
            }
        }

        if((!$count = count($node)) && $required) {
            $this->_applyMessage($node, 'required', $this->_handler->_(
                'This field requires at least one selection'
            ));
        }

        if($count && $this->_requireGroup !== null) {
            $this->_handler->setRequireGroupFulfilled($this->_requireGroup);
        }
        
        foreach($value as $key => $keyValue) {
            if(trim($keyValue) === '') {
                $this->_applyMessage($node->{$key}, 'required', $this->_handler->_(
                    'This field cannot be empty'
                ));

                continue;
            }

            if(!in_array($keyValue, $this->_options)) {
                $this->_applyMessage($node->{$key}, 'invalid', $this->_handler->_(
                    'This is not a valid option'
                ));
            }
        }

        $value = $this->_applyCustomValidator($node, $value);
        
        return $value;
    }
    
    public function applyValueTo(&$record, $value) {
        if(!is_array($record) && !$record instanceof \ArrayAccess) {
            throw new RuntimeException(
                'Target record does not implement ArrayAccess'
            );
        }
        
        if(!is_array($value)) {
            $value =[$value];
        }
        
        $record[$this->getRecordName()] = $value;
        return $this;
    }
}