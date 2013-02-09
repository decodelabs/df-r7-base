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

    public function shouldDefaultToNow($flag=null) {
        if($flag !== null) {
            $this->_defaultToNow = (bool)$flag;
            return $this;
        }

        return $this->_defaultToNow;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        
        if(!$length = $this->_checkRequired($node, $value)) {
            if($this->_defaultToNow) {
                $value = 'now';
            } else {
                return null;
            }
        }
        
        $date = core\time\Date::factory($value);

        if($this->_shouldSanitize) {
            $value = $date->toString(core\time\Date::W3C);
        }
        
        $this->_validateRange($node, $value);
        return $this->_finalize($node, $value);
    }

    protected function _validateRange(core\collection\IInputTree $node, $value) {
        if($this->_min !== null && $date->lt($this->_min)) {
            $node->addError('min', $this->_(
                'This field must be after %min%',
                array('%min%' => $this->_min->format('Y-m-d'))
            ));
        }
        
        if($this->_max !== null && $date->gt($this->_max)) {
            $node->addError('max', $this->_(
                'This field must be after %max%',
                array('%max%' => $this->_max->format('Y-m-d'))
            ));
        }
    }
}
