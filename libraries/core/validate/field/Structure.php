<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Structure extends Base implements core\validate\IStructureField {
    
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

        if($node->isEmpty() && !$node->hasValue() && $required) {
            $this->_applyMessage($node, 'required', $this->_handler->_(
                'This field cannot be empty'
            ));

            if($this->_requireGroup !== null && !$this->_handler->checkRequireGroup($this->_requireGroup)) {
                $this->_handler->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if($this->_requireGroup !== null) {
                $this->_handler->setRequireGroupFulfilled($this->_requireGroup);
            }
        }

        if($node->isEmpty()) {
            $value = $node->getValue();
        } else {
            $value = $node->toArray();
        }

        $value = $this->_sanitizeValue($value);
        $value = $this->_applyCustomValidator($node, $value);
        
        return $value;
    }
}