<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class TextList extends Base implements core\validate\ITextListField {

    protected $_allowEmptyEntries = false;

    public function shouldAllowEmptyEntries(bool $flag=null) {
        if($flag !== null) {
            $this->_allowEmptyEntries = $flag;
            return $this;
        }

        return $this->_allowEmptyEntries;
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

        if((!$count = count($node)) && $required) {
            $this->_applyMessage($node, 'required', $this->validator->_(
                'This field requires at least one selection'
            ));

            if($this->_requireGroup !== null && !$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if($this->_requireGroup !== null) {
                $this->validator->setRequireGroupFulfilled($this->_requireGroup);
            }
        }

        $value = $node->toArray();
        $value = (array)$this->_sanitizeValue($value);

        if(!$this->_allowEmptyEntries) {
            foreach($value as $key => $keyValue) {
                if(trim($keyValue) === '') {
                    $this->_applyMessage($node->{$key}, 'required', $this->validator->_(
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
            $value = [$value];
        }

        $record[$this->getRecordName()] = $value;
        return $this;
    }
}
