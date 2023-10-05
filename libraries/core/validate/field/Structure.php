<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df\core;

class Structure extends Base implements core\validate\IStructureField
{
    protected $_allowEmpty = false;



    // Options
    public function shouldAllowEmpty(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_allowEmpty = $flag;
            return $this;
        }

        return $this->_allowEmpty;
    }



    // Validate
    public function validate()
    {
        // Sanitize
        if ($this->data->isEmpty()) {
            $value = $this->data->getValue();
        } else {
            $value = $this->data->toArray();
        }

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

        if ($value === null && $required && $this->_allowEmpty) {
            $value = [];
        }


        // Validate
        if ($this->data->isEmpty() && !$this->data->hasValue() && $required && !$this->_allowEmpty) {
            $this->addError('required', $this->validator->_(
                'This field cannot be empty'
            ));

            if ($this->_requireGroup !== null && !$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if ($this->_requireGroup !== null) {
                $this->validator->setRequireGroupFulfilled($this->_requireGroup);
            }
        }


        // Finalize
        $value = $this->_sanitizeValue($value);
        $this->_applyExtension($value);

        return $value;
    }
}
