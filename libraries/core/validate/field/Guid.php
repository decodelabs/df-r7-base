<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use DecodeLabs\Guidance;
use df\core;

class Guid extends Base implements core\validate\IGuidField
{
    // Validate
    public function validate()
    {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        try {
            $value = Guidance::from($value);
        } catch (\Throwable $e) {
            $this->addError('invalid', $this->validator->_(
                'Please select a valid entry'
            ));

            return null;
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue((string)$value);

        return $value;
    }
}
