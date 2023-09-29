<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use df\core;

class Date extends Base implements core\validate\IDateField
{
    use core\validate\TRangeField;

    protected $_defaultToNow = false;
    protected $_mustBePast = false;
    protected $_mustBeFuture = false;
    protected $_expectedFormat = null;
    protected $_timezone = false;


    // Options
    public function setMin($date)
    {
        $this->_min = core\time\Date::normalize($date);
        return $this;
    }

    public function setMax($date)
    {
        $this->_max = core\time\Date::normalize($date);
        return $this;
    }

    public function shouldDefaultToNow(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_defaultToNow = $flag;
            return $this;
        }

        return $this->_defaultToNow;
    }

    public function mustBePast(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_mustBePast = $flag;

            if ($this->_mustBePast) {
                $this->_mustBeFuture = false;
            }

            return $this;
        }

        return $this->_mustBePast;
    }

    public function mustBeFuture(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_mustBeFuture = $flag;

            if ($this->_mustBeFuture) {
                $this->_mustBePast = false;
            }

            return $this;
        }

        return $this->_mustBeFuture;
    }

    public function setExpectedFormat($format)
    {
        $this->_expectedFormat = $format;
        return $this;
    }

    public function getExpectedFormat()
    {
        return $this->_expectedFormat;
    }

    public function setTimezone($timezone)
    {
        $this->_timezone = $timezone;
        return $this;
    }

    public function getTimezone()
    {
        return $this->_timezone;
    }

    public function isLocal(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_timezone = $flag;
            return $this;
        }

        return (bool)$this->_timezone;
    }



// Validate
    public function validate()
    {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if ($this->_defaultToNow && !$value && !strlen((string)$value)) {
            $value = 'now';
        }

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        try {
            if ($this->_expectedFormat) {
                $value = core\time\Date::fromFormatString($value, $this->_expectedFormat, $this->_timezone);
            } else {
                $value = core\time\Date::factory($value, $this->_timezone);
            }
        } catch (\Throwable $e) {
            $this->addError('invalid', $this->validator->_(
                'This is not a valid date'
            ));

            return $value;
        }

        $value->toUtc();

        $value = $this->_sanitizeValue($value);
        $this->_validateRange($value);

        if ($this->_mustBePast && !$value->isPast()) {
            $this->addError('mustBePast', $this->validator->_(
                'This date must not be in the future'
            ));
        }

        if ($this->_mustBeFuture && !$value->isFuture()) {
            $this->addError('mustBeFuture', $this->validator->_(
                'This date must not be in the past'
            ));
        }



        // Finalize
        $this->_applyExtension($value);

        if ($this->_expectedFormat) {
            $this->data->setValue($value->format($this->_expectedFormat));
        } else {
            $this->data->setValue($value->format(core\time\Date::W3C));
        }

        return $value;
    }

    protected function _validateRange($date)
    {
        if ($this->_min !== null && $date->lt($this->_min)) {
            $this->addError('min', $this->validator->_(
                'This field must be after %min%',
                ['%min%' => $this->_min->format('Y-m-d')]
            ));
        }

        if ($this->_max !== null && $date->gt($this->_max)) {
            $this->addError('max', $this->validator->_(
                'This field must be after %max%',
                ['%max%' => $this->_max->format('Y-m-d')]
            ));
        }
    }
}
