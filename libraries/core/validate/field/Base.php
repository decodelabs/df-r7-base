<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

abstract class Base implements core\validate\IField
{
    use core\constraint\TRequirable;
    use core\constraint\TOptional;

    public $validator;
    public $data;

    protected $_name;
    protected $_recordName = null;
    protected $_requireGroup = null;
    protected $_toggleField = null;
    protected $_sanitizer;
    protected $_defaultValue;
    protected $_extension = null;
    protected $_messageGenerator = null;
    protected $_applicator = null;


    public static function factory(core\validate\IHandler $handler, $type, $name)
    {
        if ($type === null) {
            $type = $name;
        }

        $class = 'df\\core\\validate\\field\\'.ucfirst($type);

        if (!class_exists($class)) {
            throw core\Error::ENotFound(
                'Validator type '.ucfirst($type).' could not be found for field '.$name
            );
        }

        return new $class($handler, $name);
    }


    public function __construct(core\validate\IHandler $handler, $name)
    {
        $this->validator = $handler;
        $this->_name = $name;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function setRecordName(?string $name)
    {
        $this->_recordName = $name;
        return $this;
    }

    public function getRecordName(): string
    {
        if ($this->_recordName !== null) {
            return $this->_recordName;
        } else {
            return $this->_name;
        }
    }


    // Requirements
    public function setRequireGroup(?string $name)
    {
        $this->_requireGroup = $name;
        return $this;
    }

    public function getRequireGroup(): ?string
    {
        return $this->_requireGroup;
    }

    public function setToggleField(?string $name)
    {
        $this->_toggleField = $name;
        return $this;
    }

    public function getToggleField(): ?string
    {
        return $this->_toggleField;
    }

    protected function _checkRequired($value)
    {
        $this->data->setValue($value);
        $required = $this->_isRequiredAfterToggle($value);

        if (!$length = mb_strlen($value)) {
            $value = null;

            if ($required) {
                $this->addError('required', 'This field cannot be empty');
            }

            if ($this->_requireGroup !== null && !$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } elseif ($this->_requireGroup !== null) {
            $this->validator->setRequireGroupFulfilled($this->_requireGroup);
        }

        return $length;
    }

    protected function _isRequiredAfterToggle(&$value): bool
    {
        $required = $this->_isRequired;

        if ($this->_toggleField) {
            if ($field = $this->validator->getField($this->_toggleField)) {
                $field = $this->validator->getField($this->_toggleField);
                $toggle = $this->validator[$this->_toggleField];

                if (!$field instanceof core\validate\IBooleanField) {
                    $toggle = $toggle !== null;
                }

                if ($toggle !== null) {
                    $toggle = (bool)$toggle;

                    if ($required) {
                        if (!$toggle) {
                            $this->data->setValue($value = null);
                        }

                        $required = $toggle;
                    } else {
                        if ($toggle) {
                            $this->data->setValue($value = null);
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
    public function setSanitizer(?callable $sanitizer)
    {
        $this->_sanitizer = core\lang\Callback::normalize($sanitizer);
        return $this;
    }

    public function getSanitizer(): ?callable
    {
        return $this->_sanitizer;
    }

    public function setDefaultValue($value)
    {
        $this->_defaultValue = $value;
        return $this;
    }

    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }

    protected function _sanitizeValue($value, bool $runSanitizer=true)
    {
        if ($value === '') {
            $value = null;
        }

        if ($value === null) {
            $value = $this->_defaultValue;
        }

        if ($this->_sanitizer && $runSanitizer) {
            $value = $this->_sanitizer->invoke($value, $this);
        }

        return $value;
    }



    // Extension
    public function extend(?callable $extension)
    {
        $this->_extension = core\lang\Callback::normalize($extension);
        return $this;
    }

    public function getExtension(): ?callable
    {
        return $this->_extension;
    }

    protected function _applyExtension($value)
    {
        if (!$this->data->hasErrors() && $this->_extension) {
            $this->_extension->invoke($value, $this);
        }
    }




    // Errors
    public function getDataNode(): ?core\collection\IInputTree
    {
        return $this->data;
    }

    public function isValid(): bool
    {
        if (!$this->data) {
            return false;
        }

        return $this->data->isValid();
    }

    public function countErrors(): int
    {
        if (!$this->data) {
            return 0;
        }

        return $this->data->countErrors();
    }

    public function setErrors(array $errors)
    {
        if (!$this->data) {
            return $this;
        }

        return $this->clearErrors()->addErrors($errors);
    }

    public function addErrors(array $errors)
    {
        if (!$this->data) {
            return $this;
        }

        foreach ($errors as $code => $message) {
            $this->addError($code, $message);
        }

        return $this;
    }

    public function addError($code, $defaultMessage)
    {
        if (!$this->data) {
            return $this;
        }

        $message = null;

        if ($this->_messageGenerator) {
            $message = $this->_messageGenerator->invoke($code, $this);
        }

        if (empty($message)) {
            $message = $defaultMessage;
        }

        $this->data->addError($code, $message);
        return $this;
    }

    public function getErrors()
    {
        if (!$this->data) {
            return [];
        }

        return $this->data->getErrors();
    }

    public function getError($code)
    {
        if (!$this->data) {
            return null;
        }

        return $this->data->getError($code);
    }

    public function hasErrors()
    {
        if (!$this->data) {
            return false;
        }

        return $this->data->hasErrors();
    }

    public function hasError($code)
    {
        if (!$this->data) {
            return false;
        }

        return $this->data->hasError($code);
    }

    public function clearErrors()
    {
        if (!$this->data) {
            return $this;
        }

        $this->data->clearErrors();
        return $this;
    }

    public function clearError($code)
    {
        if (!$this->data) {
            return $this;
        }

        $this->data->clearError($code);
        return $this;
    }


    public function setMessageGenerator(?callable $generator)
    {
        $this->_messageGenerator = core\lang\Callback::normalize($generator);
        return $this;
    }

    public function getMessageGenerator(): ?callable
    {
        return $this->_messageGenerator;
    }


    // Values
    public function applyValueTo(&$record, $value)
    {
        if (!is_array($record) && !$record instanceof \ArrayAccess) {
            throw core\Error::EArgument(
                'Target record does not implement ArrayAccess'
            );
        }

        if ($value === null && $this->isRequired() && !$this->isValid()) {
            return $this;
        }

        $name = $this->getRecordName();

        if ($this->_applicator) {
            $this->_applicator->invoke($record, $name, $value, $this);
        } else {
            $record[$name] = $value;
        }

        return $this;
    }

    public function setApplicator(?callable $applicator)
    {
        $this->_applicator = core\lang\Callback::normalize($applicator);
        return $this;
    }

    public function getApplicator(): ?callable
    {
        return $this->_applicator;
    }

    public function getValidator(): core\validate\IHandler
    {
        return $this->validator;
    }
}
