<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use DecodeLabs\Spectrum\Color as SpectrumColor;

use df\core;

class Color extends Base implements core\validate\IColorField
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
            $value = SpectrumColor::create($value);
        } catch (\Throwable $e) {
            $this->addError('invalid', $this->validator->_(
                'Please enter a valid color, eg. #45C34A'
            ));

            return null;
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue((string)$value);

        return $value;
    }
}
