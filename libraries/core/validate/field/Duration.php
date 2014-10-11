<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
    
class Duration extends Base implements core\validate\IDurationField {

    use core\validate\TSanitizingField;
    use core\validate\TRangeField;

    protected $_inputUnit = null;
    protected $_unitSelectable = true;

    public function setInputUnit($unit) {
        if($unit !== null) {
            $unit = core\time\Duration::normalizeUnitId($unit);
        }

        $this->_inputUnit = $unit;
        return $this;
    }

    public function getInputUnit() {
        return $this->_inputUnit;
    }

    public function shouldAllowUnitSelection($flag=null) {
        if($flag !== null) {
            $this->_unitSelectable = (bool)$flag;
            return $this;
        }

        return $this->_unitSelectable;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if($this->_unitSelectable && ($unit = $node->unit->getValue())) {
            $this->_inputUnit = $unit;
        }

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        try {
            $duration = $this->_normalizeDuration($value);
        } catch(\Exception $e) {
            $this->_applyMessage($node, 'invalid', $this->_handler->_(
                'This is not a valid duration'
            ));

            return $value;
        }

        $this->_validateRange($node, $duration);

        return $this->_finalize($node, $duration);
    }

    public function applyValueTo(&$record, $value) {
        return parent::applyValueTo($record, $this->_normalizeDuration($value));
    }

    protected function _normalizeDuration($value) {
        if($this->_inputUnit) {
            $duration = core\time\Duration::fromUnit($value, $this->_inputUnit);
        } else {
            $duration = core\time\Duration::factory($value);
        }

        return $duration;
    }

    protected function _validateRange(core\collection\IInputTree $node, $value) {
        if(!$value instanceof core\time\IDuration) {
            $value = $this->_normalizeDuration($value);
        }

        if($this->_min !== null) {
            $min = core\time\Duration::factory($this->_min);

            if($value->lt($min)) {
                $this->_applyMessage($node, 'min', $this->_handler->_(
                    'This field must be at least %min%',
                    ['%min%' => $min]
                ));
            }
        }
        
        if($this->_max !== null) {
            $max = core\time\Duration::factory($this->_max);

            if($value->gt($max)) {
                $this->_applyMessage($node, 'max', $this->_handler->_(
                    'This field must not be more than %max%',
                    ['%max%' => $max]
                ));
            }
        }
    }
}