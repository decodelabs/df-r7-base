<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use DecodeLabs\Dictum;

use df\core;

class Boolean extends Base implements core\validate\IBooleanField
{
    use core\validate\TRequiredValueField;

    protected $_isRequired = false;
    protected $_forceAnswer = true;


    // Options
    public function shouldForceAnswer(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_forceAnswer = $flag;
            return $this;
        }

        return $this->_forceAnswer;
    }

    protected function _prepareRequiredValue($value)
    {
        return Dictum::toBoolean($value);
    }


    // Validate
    public function validate()
    {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());
        $isRequired = $this->_isRequiredAfterToggle($value);

        if (!is_bool($value)) {
            if (!$length = strlen((string)$value)) {
                $value = null;

                if ($this->_isRequired && $this->_forceAnswer) {
                    $value = false;
                }
            } else {
                if (is_string($value)) {
                    $value = Dictum::toBoolean($value);
                } else {
                    $value = (bool)$value;
                }
            }
        }


        // Validate
        if ($isRequired && $value === null) {
            $this->addError('required', $this->validator->_(
                'This field requires an answer'
            ));
        } else {
            $this->_checkRequiredValue($value, $isRequired);
        }

        if ($this->_requireGroup !== null) {
            $check = $isRequired ? $value !== null : (bool)$value;

            if ($check) {
                $this->validator->setRequireGroupFulfilled($this->_requireGroup);
            } elseif (!$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
