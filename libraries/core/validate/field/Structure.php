<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Structure extends Base implements core\validate\IStructureField {
    
    protected $_allowEmpty = false;

    public function shouldAllowEmpty($flag=null) {
        if($flag !== null) {
            $this->_allowEmpty = (bool)$flag;
            return $this;
        }

        return $this->_allowEmpty;
    }

    public function validate(core\collection\IInputTree $node) {
        $required = $this->_isRequired;

        if($this->_toggleField) {
            if($field = $this->validator->getField($this->_toggleField)) {
                $toggle = (bool)$this->validator[$this->_toggleField];

                if(!$toggle) {
                    $node->setValue($value = []);
                }

                if($required) {
                    $required = $toggle;
                }
            }
        }

        if($node->isEmpty()) {
            $value = $node->getValue();
        } else {
            $value = $node->toArray();
        }

        if($value === null && $required && $this->_allowEmpty) {
            $value = [];
        }

        if($node->isEmpty() && !$node->hasValue() && $required && !$this->_allowEmpty) {
            $this->_applyMessage($node, 'required', $this->validator->_(
                'This field cannot be empty'
            ));

            if($this->_requireGroup !== null && !$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if($this->_requireGroup !== null) {
                $this->validator->setRequireGroupFulfilled($this->_requireGroup);
            }
        }

        $value = $this->_sanitizeValue($value);
        $value = $this->_applyCustomValidator($node, $value);
        
        return $value;
    }
}