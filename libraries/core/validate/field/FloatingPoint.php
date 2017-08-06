<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class FloatingPoint extends Base implements core\validate\IFloatingPointField {

    use core\validate\TRangeField;



// Validate
    public function validate() {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        if(false === filter_var($value, FILTER_VALIDATE_FLOAT) && $value !== '0') {
            $this->addError('invalid', $this->validator->_(
                'This is not a valid number'
            ));
        } else {
            $value = (float)$value;
        }

        $this->_validateRange($value);


        // Finalize
        $value = $this->_applyCustomValidator($value);
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
