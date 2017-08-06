<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\neon;

class Color extends Base implements core\validate\IColorField {


// Validate
    public function validate() {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }



        // Validate
        try {
            $value = neon\Color::factory($value);
        } catch(\Throwable $e) {
            $this->addError('invalid', $this->validator->_(
                'Please enter a valid color'
            ));

            return null;
        }


        // Finalize
        $value = $this->_applyCustomValidator($value);
        $this->_applyExtension($value);
        $this->data->setValue((string)$value);

        return $value;
    }
}
