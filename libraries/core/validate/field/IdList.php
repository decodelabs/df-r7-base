<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df\core;

class IdList extends Base implements core\validate\IIdListField
{
    protected $_useKeys = false;


    // Options
    public function shouldUseKeys(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_useKeys = $flag;
            return $this;
        }

        return $this->_useKeys;
    }



    // Validate
    public function validate()
    {
        // Sanitize
        $value = $this->data->toArray();

        if ($this->_useKeys) {
            $value = array_keys($value);
        }

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


        // Validate
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


        // Finalize
        $this->_applyExtension($value);

        return $value;
    }


    // Apply
    public function applyValueTo(&$record, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        return parent::applyValueTo($record, $value);
    }
}
