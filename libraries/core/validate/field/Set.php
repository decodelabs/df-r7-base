<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Set extends Base implements core\validate\IEnumField {

    protected $_options = [];
    protected $_stringDelimiter = null;

    public function setOptions(array $options) {
        $this->_options = $options;
        return $this;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function applyAsString($delimiter) {
        if($delimiter === false) {
            $delimiter = null;
        } else {
            $delimiter = (string)$delimiter;
        }

        $this->_stringDelimiter = $delimiter;
        return $this;
    }

    public function shouldApplyAsString() {
        return $this->_stringDelimiter !== null;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->toArray();
        $value = (array)$this->_sanitizeValue($value);
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
        }

        if($count && $this->_requireGroup !== null) {
            $this->validator->setRequireGroupFulfilled($this->_requireGroup);
        }

        $hasOptions = !empty($this->_options);

        foreach($value as $key => $keyValue) {
            if(trim($keyValue) === '') {
                $this->_applyMessage($node->{$key}, 'required', $this->validator->_(
                    'This field cannot be empty'
                ));

                continue;
            }


            if($hasOptions && !in_array($keyValue, $this->_options)) {
                $this->_applyMessage($node->{$key}, 'invalid', $this->validator->_(
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
            $value = [$value];
        }

        if($this->_stringDelimiter !== null) {
            $value = implode($this->_stringDelimiter, $value);
        }

        $record[$this->getRecordName()] = $value;
        return $this;
    }
}