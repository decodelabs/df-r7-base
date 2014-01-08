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

class DatePicker extends NumberTextbox implements IDateWidget {
    
    const INPUT_TYPE = null;

    protected $_outputFormat = 'Y-m-d';
    protected $_placeholder = 'yyyy-MM-dd';
    
    public function __construct(arch\IContext $context, $name, $value=null, $outputFormat=null) {
        if($outputFormat !== null) {
            $this->_outputFormat = $outputFormat;
        }

        parent::__construct($context, $name, $value);
    }

    protected function _render() {
        $tag = $this->getTag();
        $tag->setAttribute('type', $this->_getInputType());

        if($this->_placeholder !== null) {
            $tag->setAttribute('placeholder', $this->_placeholder);
        }
        
        return parent::_render();
    }

    protected function _getInputType() {
        if(static::INPUT_TYPE !== null) {
            return static::INPUT_TYPE;
        }

        if($this->_outputFormat != 'Y-m-d') {
            return 'text';
        } else {
            return 'date';
        }
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
    
    public function getOutputFormat() {
        return $this->_outputFormat;
    }

    public function setPlaceholder($placeholder) {
        $this->_placeholder = $placeholder;
        return $this;
    }
    
    public function getPlaceholder() {
        return $this->_placeholder;
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
        if($this->_outputFormat != 'Y-m-d') {
            return core\time\Date::fromFormatString((string)$date, $this->_outputFormat);
        } else {
            return core\time\Date::factory((string)$date);
        }
    }
    
    protected function _dateToString(core\time\IDate $date) {
        return $date->format($this->_outputFormat);
    }
}
