<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Integer extends Base implements core\validate\IIntegerField {

    use core\validate\TRangeField;



// Validate
    public function validate() {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        $options = ['flags' => FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX];

        if(false === filter_var($value, FILTER_VALIDATE_INT, $options)) {
            $this->addError('invalid', $this->validator->_(
                'This is not a valid number'
            ));
        } else {
            $value = (int)$value;
        }

        $this->_validateRange($value);


        // Finalize
        $value = $this->_applyCustomValidator($value);
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
