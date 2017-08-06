<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\opal;

class Time extends Base implements core\validate\ITimeField {


// Validate
    public function validate() {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        try {
            $value = core\time\TimeOfDay::factory($value);
        } catch(\Throwable $e) {
            $this->addError('invalid', $this->validator->_(
                'This is not a valid time of day'
            ));
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
