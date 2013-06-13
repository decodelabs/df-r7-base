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
    use core\validate\TSanitizingField;

    protected $_defaultToNow = false;
    protected $_mustBePast = false;
    protected $_mustBeFuture = false;
    
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

    public function mustBePast($flag=null) {
        if($flag !== null) {
            $this->_mustBePast = (bool)$flag;

            if($this->_mustBePast) {
                $this->_mustBeFuture = false;
            }

            return $this;
        }

        return $this->_mustBePast;
    }

    public function mustBeFuture($flag=null) {
        if($flag !== null) {
            $this->_mustBeFuture = (bool)$flag;

            if($this->_mustBeFuture) {
                $this->_mustBePast = false;
            }

            return $this;
        }

        return $this->_mustBeFuture;
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
        $date = $this->_sanitizeValue($date);
        $this->_validateRange($node, $date);

        if($this->_mustBePast && !$date->isPast()) {
            $node->addError('future', $this->_handler->_('This date must not be in the future'));
        }

        if($this->_mustBeFuture && !$date->isFuture()) {
            $node->addError('future', $this->_handler->_('This date must not be in the past'));
        }

        if($this->_shouldSanitize) {
            $value = $date->toString(core\time\Date::W3C);
        }
        
        return $this->_finalize($node, $value);
    }

    protected function _validateRange(core\collection\IInputTree $node, $date) {
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
