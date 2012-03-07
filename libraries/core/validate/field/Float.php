<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Float extends Base {
    
    protected $_minRange = null;
    protected $_maxRange = null;
    
    public function setMin($min) {
        if($min !== null) {
            $min = (float)$min;
        }
        
        $this->_minRange = $min;
        return $this;
    }
    
    public function getMin() {
        return $this->_min;
    }
    
    public function setMax($max) {
        if($max !== null) {
            $max = (float)$max;
        }
        
        $this->_maxRange = $max;
        return $this;
    }
    
    public function getMax() {
        return $this->_maxRange;
    }
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        if(!filter_var($value, FILTER_VALIDATE_INT)) {
            $node->addError('invalid', $this->_(
                'This is not a valid number'
            ));
        } else {
            $value = (float)$value;
        }
        
        if($this->_minRange !== null && $value < $this->_minRange) {
            $node->addError('min', $this->_handler->_(
                'This field must be at least %min%',
                array('%min%' => $this->_minRange)
            ));
        }
        
        if($this->_maxRange !== null && $value > $this->_maxRange) {
            $node->addError('max', $this->_handler->_(
                'This field must not be more than %max%',
                array('%max%' => $this->_maxRange)
            ));
        }
        
        return $this->_finalize($node, $value);
    }
}
