<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

abstract class Base implements core\validate\IField {

    use core\constraint\TRequirable;
    use core\constraint\TOptional;

    public $validator;

    protected $_name;
    protected $_recordName = null;
    protected $_requireGroup = null;
    protected $_toggleField = null;
    protected $_shouldSanitize = true;
    protected $_sanitizer;
    protected $_defaultValue;
    protected $_customValidator = null;
    protected $_messageGenerator = null;


    public static function factory(core\validate\IHandler $handler, $type, $name) {
        if($type === null) {
            $type = $name;
        }

        $class = 'df\\core\\validate\\field\\'.ucfirst($type);

        if(!class_exists($class)) {
            throw new core\validate\RuntimeException(
                'Validator type '.ucfirst($type).' could not be found for field '.$name
            );
        }

        return new $class($handler, $name);
    }


    public function __construct(core\validate\IHandler $handler, $name) {
        $this->validator = $handler;
        $this->_name = $name;
    }

    public function getName() {
        return $this->_name;
    }

    public function setRecordName($name) {
        $this->_recordName = $name;
        return $this;
    }

    public function getRecordName() {
        if($this->_recordName) {
            return $this->_recordName;
        } else {
            return $this->_name;
        }
    }


// Requirements
    public function setRequireGroup($name) {
        $this->_requireGroup = $name;
        return $this;
    }

    public function getRequireGroup() {
        return $this->_requireGroup;
    }

    public function setToggleField($name) {
        $this->_toggleField = $name;
        return $this;
    }

    public function getToggleField() {
        return $this->_toggleField;
    }

    protected function _checkRequired(core\collection\IInputTree $node, $value) {
        if($this->_shouldSanitize) {
            $node->setValue($value);
        }

        $required = $this->_isRequiredAfterToggle($node, $value);

        if(!$length = mb_strlen($value)) {
            $value = null;

            if($required) {
                $this->_applyMessage($node, 'required', 'This field cannot be empty');
            }

            if($this->_requireGroup !== null && !$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else if($this->_requireGroup !== null) {
            $this->validator->setRequireGroupFulfilled($this->_requireGroup);
        }

        return $length;
    }

    protected function _isRequiredAfterToggle(core\collection\IInputTree $node, &$value) {
        $required = $this->_isRequired;

        if($this->_toggleField) {
            if($field = $this->validator->getField($this->_toggleField)) {
                $field = $this->validator->getField($this->_toggleField);
                $toggle = $this->validator[$this->_toggleField];

                if(!$field instanceof core\validate\IBooleanField) {
                    $toggle = $toggle !== null;
                }

                if($toggle !== null) {
                    $toggle = (bool)$toggle;

                    if($required) {
                        if(!$toggle) {
                            $node->setValue($value = null);
                        }

                        $required = $toggle;
                    } else {
                        if($toggle) {
                            $node->setValue($value = null);
                        }

                        $required = !$toggle;
                    }
                } else {
                    $required = false;
                }
            }
        }

        return $required;
    }



// Sanitize
    public function shouldSanitize(bool $flag=null) {
        if($flag !== null) {
            $this->_shouldSanitize = $flag;
            return $this;
        }

        return $this->_shouldSanitize;
    }

    public function setSanitizer($sanitizer) {
        if($sanitizer !== null) {
            $sanitizer = core\lang\Callback::factory($sanitizer);
        }

        $this->_sanitizer = $sanitizer;
        return $this;
    }

    public function getSanitizer() {
        return $this->_sanitizer;
    }

    public function setDefaultValue($value) {
        $this->_defaultValue = $value;
        return $this;
    }

    public function getDefaultValue() {
        return $this->_defaultValue;
    }

    protected function _sanitizeValue($value, $runSanitizer=true) {
        if($value === '') {
            $value = null;
        }

        if($value === null) {
            $value = $this->_defaultValue;
        }

        if($this->_sanitizer && $runSanitizer) {
            $value = $this->_sanitizer->invoke($value, $this);
        }

        return $value;
    }



// Custom validator
    public function setCustomValidator($validator=null) {
        if($validator !== null) {
            $validator = core\lang\Callback::factory($validator);
        }

        $this->_customValidator = $validator;
        return $this;
    }

    public function getCustomValidator() {
        return $this->_customValidator;
    }

    protected function _applyCustomValidator(core\collection\IInputTree $node, $value) {
        if(!$node->hasErrors() && $this->_customValidator) {
            $this->_customValidator->invoke($node, $value, $this);
        }

        return $value;
    }


// Messages
    public function setMessageGenerator($generator=null) {
        if($generator !== null) {
            $generator = core\lang\Callback::factory($generator);
        }

        $this->_messageGenerator = $generator;
        return $this;
    }

    public function getMessageGenerator() {
        return $this->_messageGenerator;
    }

    protected function _applyMessage(core\collection\IInputTree $node, $code, $defaultMessage) {
        $message = null;

        if($this->_messageGenerator) {
            $message = $this->_messageGenerator->invoke($code, $this, $this->validator);
        }

        if(empty($message)) {
            $message = $defaultMessage;
        }

        $node->addError($code, $message);
    }


// Values
    public function applyValueTo(&$record, $value) {
        if(!is_array($record) && !$record instanceof \ArrayAccess) {
            throw new RuntimeException(
                'Target record does not implement ArrayAccess'
            );
        }

        $name = $this->getRecordName();

        //if($value !== null || !isset($record[$name])) {
            $record[$name] = $value;
        //}

        return $this;
    }

    protected function _finalize(core\collection\IInputTree $node, $value) {
        $value = $this->_applyCustomValidator($node, $value);

        if($this->_shouldSanitize) {
            $node->setValue($value);
        }

        return $value;
    }
}