<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Date extends Base implements core\validate\IDateField {

    use core\validate\TRangeField;

    protected $_defaultToNow = false;
    protected $_mustBePast = false;
    protected $_mustBeFuture = false;
    protected $_expectedFormat = null;
    protected $_timezone = false;

    public function setMin($date) {
        if($date !== null) {
            $date = core\time\Date::factory($date);
        }

        $this->_min = $date;
        return $this;
    }

    public function setMax($date) {
        if($date !== null) {
            $date = core\time\Date::factory($date);
        }

        $this->_max = $date;
        return $this;
    }

    public function shouldDefaultToNow(bool $flag=null) {
        if($flag !== null) {
            $this->_defaultToNow = $flag;
            return $this;
        }

        return $this->_defaultToNow;
    }

    public function mustBePast(bool $flag=null) {
        if($flag !== null) {
            $this->_mustBePast = $flag;

            if($this->_mustBePast) {
                $this->_mustBeFuture = false;
            }

            return $this;
        }

        return $this->_mustBePast;
    }

    public function mustBeFuture(bool $flag=null) {
        if($flag !== null) {
            $this->_mustBeFuture = $flag;

            if($this->_mustBeFuture) {
                $this->_mustBePast = false;
            }

            return $this;
        }

        return $this->_mustBeFuture;
    }

    public function setExpectedFormat($format) {
        $this->_expectedFormat = $format;
        return $this;
    }

    public function getExpectedFormat() {
        return $this->_expectedFormat;
    }

    public function setTimezone($timezone) {
        $this->_timezone = $timezone;
        return $this;
    }

    public function getTimezone() {
        return $this->_timezone;
    }

    public function isLocal(bool $flag=null) {
        if($flag !== null) {
            $this->_timezone = $flag;
            return $this;
        }

        return (bool)$this->_timezone;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if($this->_defaultToNow && !$value && !strlen($value)) {
            $value = 'now';
        }

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        try {
            if($this->_expectedFormat) {
                $date = core\time\Date::fromFormatString($value, $this->_expectedFormat, $this->_timezone);
            } else {
                $date = core\time\Date::factory($value, $this->_timezone);
            }
        } catch(\Exception $e) {
            $this->_applyMessage($node, 'invalid', $this->validator->_(
                'This is not a valid date'
            ));

            return $value;
        }

        $date->toUtc();

        $date = $this->_sanitizeValue($date);
        $this->_validateRange($node, $date);

        if($this->_mustBePast && !$date->isPast()) {
            $this->_applyMessage($node, 'mustBePast', $this->validator->_(
                'This date must not be in the future'
            ));
        }

        if($this->_mustBeFuture && !$date->isFuture()) {
            $this->_applyMessage($node, 'mustBeFuture', $this->validator->_(
                'This date must not be in the past'
            ));
        }

        $value = $this->_applyCustomValidator($node, $date);

        if($this->_shouldSanitize) {
            if($this->_expectedFormat) {
                $node->setValue($value->format($this->_expectedFormat));
            } else {
                $node->setValue($value->format(core\time\Date::W3C));
            }
        }

        return $value;
    }

    protected function _validateRange(core\collection\IInputTree $node, $date) {
        if($this->_min !== null && $date->lt($this->_min)) {
            $this->_applyMessage($node, 'min', $this->validator->_(
                'This field must be after %min%',
                ['%min%' => $this->_min->format('Y-m-d')]
            ));
        }

        if($this->_max !== null && $date->gt($this->_max)) {
            $this->_applyMessage($node, 'max', $this->validator->_(
                'This field must be after %max%',
                ['%max%' => $this->_max->format('Y-m-d')]
            ));
        }
    }
}
