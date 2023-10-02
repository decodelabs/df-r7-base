<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use df\core;

class TextList extends Base implements core\validate\ITextListField
{
    protected $_allowEmptyEntries = false;
    protected $_filterEmptyEntries = false;


    // Options
    public function shouldAllowEmptyEntries(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_allowEmptyEntries = $flag;
            return $this;
        }

        return $this->_allowEmptyEntries;
    }

    public function shouldFilterEmptyEntries(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_filterEmptyEntries = $flag;
            return $this;
        }

        return $this->_filterEmptyEntries;
    }


    // Validate
    public function validate()
    {
        // Sanitize
        $value = $this->data->toArray();
        $value = (array)$this->_sanitizeValue($value);

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

        if ((!$count = count($this->data)) && $required) {
            $this->addError('required', $this->validator->_(
                'This field requires at least one selection'
            ));

            if ($this->_requireGroup !== null && !$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if ($this->_requireGroup !== null) {
                $this->validator->setRequireGroupFulfilled($this->_requireGroup);
            }
        }


        // Validate
        if (!$this->_allowEmptyEntries || $this->_filterEmptyEntries) {
            foreach ($value as $key => $keyValue) {
                if (trim((string)$keyValue) === '') {
                    if ($this->_filterEmptyEntries) {
                        unset($value[$key]);
                    } else {
                        $this->data->{$key}->addError('required', $this->validator->_(
                            'This field cannot be empty'
                        ));
                    }
                }
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

        return parent::applyValueTo($record, $value);
    }
}
