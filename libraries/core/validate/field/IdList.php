<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class IdList extends Base implements core\validate\IIdListField {
    
    use core\validate\TSanitizingField;

    protected $_useKeys = false;

    public function shouldUseKeys($flag=null) {
        if($flag !== null) {
            $this->_useKeys = (bool)$flag;
            return $this;
        }

        return $this->_useKeys;
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
            $this->_applyMessage($node, 'required', $this->_handler->_(
                'This field requires at least one selection'
            ));

            if($this->_requireGroup !== null && !$this->_handler->checkRequireGroup($this->_requireGroup)) {
                $this->_handler->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if($this->_requireGroup !== null) {
                $this->_handler->setRequireGroupFulfilled($this->_requireGroup);
            }
        }

        $value = $node->toArray();
        
        if($this->_useKeys) {
            $value = array_keys($value);
        }
        
        $value = (array)$this->_sanitizeValue($value);
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
            $value = [$value];
        }
        
        $record[$this->getRecordName()] = $value;
        return $this;
    }
}
