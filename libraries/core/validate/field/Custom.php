<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\opal;

class Custom extends Base implements core\validate\IField {


// Validate
    public function validate() {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
