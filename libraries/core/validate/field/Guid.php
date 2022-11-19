<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df\core;
use df\flex;

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
            $value = flex\Guid::factory($value);
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
