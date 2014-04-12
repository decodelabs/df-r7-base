<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class TextList extends Base implements core\validate\ITextListField {
    
    use core\validate\TSanitizingField;

    protected $_allowEmptyEntries = false;

    public function shouldAllowEmptyEntries($flag=null) {
        if($flag !== null) {
            $this->_allowEmptyEntries = (bool)$flag;
            return $this;
        }

        return $this->_allowEmptyEntries;
    }

    public function validate(core\collection\IInputTree $node) {
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
            $node->addError('required', $this->_handler->_(
                'This field requires at least one selection'
            ));
        }
        
        $value = $node->toArray();
        $value = (array)$this->_sanitizeValue($value);

        if(!$this->_allowEmptyEntries) {
            foreach($value as $key => $keyValue) {
                if(trim($keyValue) === '') {
                    $node->{$key}->addError('required', $this->_handler->_(
                        'This field cannot be empty'
                    ));
                }
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
            $value = array($value);
        }
        
        $record[$this->getRecordName()] = $value;
        return $this;
    }
}
