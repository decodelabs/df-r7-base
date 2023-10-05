<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df\core;

class Set extends Base implements core\validate\IEnumField
{
    use core\validate\TOptionProviderField;

    protected $_stringDelimiter = null;


    // Options
    public function applyAsString($delimiter)
    {
        if ($delimiter === false) {
            $delimiter = null;
        } else {
            $delimiter = (string)$delimiter;
        }

        $this->_stringDelimiter = $delimiter;
        return $this;
    }

    public function shouldApplyAsString()
    {
        return $this->_stringDelimiter !== null;
    }



    // Validate
    public function validate()
    {
        // Sanitize
        $value = (array)$this->_sanitizeValue($this->data->toArray());
        $required = $this->_isRequired;

        if ($this->_toggleField) {
            if ($field = $this->validator->getField($this->_toggleField)) {
                $toggle = (bool)$this->validator[$this->_toggleField];

                if (!$toggle) {
                    $this->data->setValue($value = []);
                }

                if ($required) {
                    $required = $toggle;
                }
            }
        }



        // Validate
        if ((!$count = count($this->data)) && $required) {
            $this->addError('required', $this->validator->_(
                'This field requires at least one selection'
            ));
        }

        if ($count && $this->_requireGroup !== null) {
            $this->validator->setRequireGroupFulfilled($this->_requireGroup);
        }

        if ($this->_type) {
            $options = $this->_type->getOptions();
        } else {
            $options = $this->_options;
        }

        $hasOptions = !empty($options);

        foreach ($value as $key => $keyValue) {
            if (trim($keyValue) === '') {
                $this->data->{$key}->addError('required', $this->validator->_(
                    'This field cannot be empty'
                ));

                continue;
            }


            if ($hasOptions && !in_array($keyValue, $options)) {
                $this->data->{$key}->addError('invalid', $this->validator->_(
                    'This is not a valid option'
                ));
            }
        }



        // Finalize
        $this->_applyExtension($value);

        return $value;
    }

    public function applyValueTo(&$record, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        if ($this->_stringDelimiter !== null) {
            $value = implode($this->_stringDelimiter, $value);
        }

        return parent::applyValueTo($record, $value);
    }
}
