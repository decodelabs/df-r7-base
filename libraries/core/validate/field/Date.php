<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Date extends Base {
    
    protected $_min = null;
    protected $_max = null;
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        $date = core\time\Date::factory($value);
        
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
        
        return $this->_finalize($node, $value);
    }
    
    public function setMin($date) {
        if($date !== null) {
            $date = core\time\Date::factory($date);
        }
        
        $this->_min = $date;
        return $this;
    }
    
    public function getMin() {
        return $this->_min;
    }
    
    public function setMax($date) {
        if($date !== null) {
            $date = core\time\Date::factory($date);
        }
        
        $this->_max = $date;
        return $this;
    }
}
