<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class DatePicker extends NumberTextbox implements IDateWidget {
    
    const INPUT_TYPE = 'date';
    const DEFAULT_PLACEHOLDER = 'yyyy-MM-dd';
    
    protected function _render() {
        if(static::DEFAULT_PLACEHOLDER !== null) {
            $this->getTag()->setAttribute('placeholder', static::DEFAULT_PLACEHOLDER);
        }
        
        return parent::_render();
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
            $innerValue = $this->_normalizeDateString($innerValue);
        }
        
        if($value instanceof core\IValueContainer) {
            $value->setValue($innerValue);
        } else {
            $value = $innerValue;
        }
        
        return parent::setValue($value);
    }
    
    public function setMin($min) {
        return parent::setMin($this->_normalizeDateString($min));
    }
    
    public function setMax($max) {
        return parent::setMax($this->_normalizeDateString($max));
    }
    
    protected function _normalizeDateString($date) {
        if(!$date instanceof core\time\IDate) {
            try {
                $date = $this->_stringToDate($date);
            } catch(\Exception $e) {
                $date = null;
            }
        }
        
        if($date !== null) {
            $date = $this->_dateToString($date);
        }
        
        return $date;
    }
    
    protected function _stringToDate($date) {
        return core\time\Date::factory($date);
    }
    
    protected function _dateToString(core\time\IDate $date) {
        return $date->format('Y-m-d');
    }
}
