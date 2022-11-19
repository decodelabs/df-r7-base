<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df\core;

class Enum extends Base implements core\validate\IEnumField
{
    use core\validate\TOptionProviderField;



    // Validate
    public function validate()
    {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        if ($this->_type) {
            try {
                $value = $this->_type->factory($value)->getOption();
            } catch (core\lang\EnumException $e) {
                $this->addError('invalid', $this->validator->_(
                    'Please select a valid option'
                ));
            }
        } else {
            if (!in_array($value, $this->_options)) {
                $this->addError('invalid', $this->validator->_(
                    'Please select a valid option'
                ));
            }
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
