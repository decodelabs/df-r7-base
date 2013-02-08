<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;
    
class Duration extends NumberTextbox {

    protected $_inputUnit = core\time\Duration::SECONDS;

    protected function _render() {
        $output = parent::_render();

        if($this->_inputUnit) {
            $unit = core\time\Duration::getUnitString($this->_inputUnit);
            $output = new aura\html\Element('label', [$output, ' ', $unit]);
        }

        return $output;
    }

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

    public function setValue($value) {
        $innerValue = $value;
        
        if($innerValue instanceof core\IValueContainer) {
            $innerValue = $innerValue->getValue();
        }

        if(is_string($innerValue) && !strlen($innerValue)) {
            $innerValue = null;
        }
        
        if($innerValue !== null) {
            $innerValue = $this->_normalizeDurationString($innerValue);
        }
        
        if($value instanceof core\IValueContainer) {
            $value->setValue($innerValue);
        } else {
            $value = $innerValue;
        }

        return parent::setValue($value);
    }

    public function setMin($min) {
        return parent::setMin($this->_normalizeDurationString($min));
    }
    
    public function setMax($max) {
        return parent::setMax($this->_normalizeDurationString($max));
    }

    protected function _normalizeDurationString($duration) {
        if($this->_inputUnit) {
            $duration = core\time\Duration::fromUnit($duration, $this->_inputUnit);
            return $duration->toUnit($this->_inputUnit);
        } else {
            $duration = core\time\Duration::factory($duration);
            return $duration->toString();
        }
    }
}