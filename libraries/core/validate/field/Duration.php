<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df\core;

class Duration extends Base implements core\validate\IDurationField
{
    use core\validate\TRangeField;

    protected $_inputUnit = null;
    protected $_unitSelectable = true;


    // Options
    public function setInputUnit($unit)
    {
        if ($unit !== null) {
            $unit = core\time\Duration::normalizeUnitId($unit);
        }

        $this->_inputUnit = $unit;
        return $this;
    }

    public function getInputUnit()
    {
        return $this->_inputUnit;
    }

    public function shouldAllowUnitSelection(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_unitSelectable = $flag;
            return $this;
        }

        return $this->_unitSelectable;
    }

    public function setMin($min)
    {
        $this->_min = $min;
        return $this;
    }

    public function setMax($max)
    {
        $this->_max = $max;
        return $this;
    }



    // Validate
    public function validate()
    {
        $value = $this->data->getValue();
        $value = $this->_sanitizeValue($value);

        if ($this->_unitSelectable && ($unit = $this->data->unit->getValue())) {
            $this->_inputUnit = $unit;
        }

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }

        try {
            $duration = $this->_normalizeDuration($value);
        } catch (\Throwable $e) {
            $this->addError('invalid', $this->validator->_(
                'This is not a valid duration'
            ));

            return $value;
        }

        $this->_validateRange($duration);

        $this->_applyExtension($duration);
        $this->data->setValue($value);

        return $duration;
    }

    protected function _normalizeDuration($value)
    {
        if ($this->_inputUnit) {
            $duration = core\time\Duration::fromUnit($value, $this->_inputUnit);
        } else {
            $duration = core\time\Duration::factory($value);
        }

        return $duration;
    }

    protected function _validateRange($value)
    {
        if (!$value instanceof core\time\IDuration) {
            $value = $this->_normalizeDuration($value);
        }

        if ($this->_min !== null) {
            $min = core\time\Duration::factory($this->_min);

            if ($value->lt($min)) {
                $this->addError('min', $this->validator->_(
                    'This field must be at least %min%',
                    ['%min%' => $min]
                ));
            }
        }

        if ($this->_max !== null) {
            $max = core\time\Duration::factory($this->_max);

            if ($value->gt($max)) {
                $this->addError('max', $this->validator->_(
                    'This field must not be more than %max%',
                    ['%max%' => $max]
                ));
            }
        }
    }


    // Apply
    public function applyValueTo(&$record, $value)
    {
        if ($value !== null) {
            $value = $this->_normalizeDuration($value);
        }

        return parent::applyValueTo($record, $value);
    }
}
