<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use DecodeLabs\Exceptional;

use df\core;

class FileSize extends Base implements core\validate\IFileSizeField
{
    use core\validate\TRangeField;

    protected $_allowZero = false;

    public function setMin($date)
    {
        $this->_min = core\unit\FileSize::normalize($date);

        if ($this->_min && !$this->_min->getBytes() && !$this->_allowZero) {
            throw Exceptional::InvalidArgument(
                'Byte value must be greater than one'
            );
        }

        return $this;
    }

    public function setMax($date)
    {
        $this->_max = core\unit\FileSize::normalize($date);

        if ($this->_max && !$this->_max->getBytes() && !$this->_allowZero) {
            throw Exceptional::InvalidArgument(
                'Byte value must be greater than one'
            );
        }

        return $this;
    }

    public function shouldAllowZero(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_allowZero = $flag;
            return $this;
        }

        return $this->_allowZero;
    }


    // Validate
    public function validate()
    {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        $bytes = null;

        try {
            $value = core\unit\FileSize::factory($value);
            $bytes = $value->getBytes();
        } catch (\Throwable $e) {
            $this->addError('invalid', $this->validator->_(
                'Please enter a valid file size'
            ));

            return null;
        }


        if (!$bytes && !$this->_allowZero) {
            $this->addError('invalid', $this->validator->_(
                'Please enter a valid file size'
            ));
        }


        $this->_validateRange($value);


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue((string)$value);

        return $value;
    }

    protected function _validateRange($value)
    {
        if ($this->_min !== null && $value->getBytes() < $this->_min->getBytes()) {
            $this->addError('min', $this->validator->_(
                'This field must be greater than %min%',
                ['%min%' => $this->_min]
            ));
        }

        if ($this->_max !== null && $value->getBytes() > $this->_max->getBytes()) {
            $this->addError('max', $this->validator->_(
                'This field must be less than %max%',
                ['%max%' => $this->_max]
            ));
        }
    }
}
