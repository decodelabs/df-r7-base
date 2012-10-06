<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Text extends Base implements core\validate\ITextField {
    
    protected $_pattern = null;
    protected $_minLength = null;
    protected $_maxLength = null;
    protected $_sanitizer;
    
    
    public function setSanitizer(Callable $sanitizer) {
        $this->_sanitizer = $sanitizer;
        return $this;
    }
    
    public function getSanitizer() {
        return $this->_sanitizer;
    }
    
    public function setPattern($pattern) {
        if(empty($pattern)) {
            $pattern = null;
        }
        
        $this->_pattern = $pattern;
        return $this;
    }
    
    public function getPattern() {
        return $this->_pattern;
    }
    
    
    public function setMinLength($length) {
        if(empty($length)) {
            $length = null;
        }
        
        $this->_minLength = $length;
        return $this;
    }
    
    public function getMinLength() {
        return $this->_minLength;
    }
    
    
    public function setMaxLength($length) {
        if(empty($length)) {
            $length = null;
        }
        
        $this->_maxLength = $length;
        return $this;
    }
    
    public function getMaxLength() {
        return $this->_maxLength;
    }
    
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        
        if($this->_sanitizer) {
            $value = call_user_func_array($this->_sanitizer, [$value]);
        }
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        
        if($this->_minLength !== null && $length < $this->_minLength) {
            $node->addError('minLength', $this->_handler->_(
                array(
                    'n = 1 || n = -1' => 'This field must be at least %min% character',
                    '*' => 'This field must be at least %min% characters'
                ),
                array('%min%' => $this->_minLength),
                $this->_minLength
            ));
        }
        
        if($this->_maxLength !== null && $length > $this->_maxLength) {
            $node->addError('maxLength', $this->_handler->_(
                array(
                    'n = 1 || n = -1' => 'This field must not me more than %max% character',
                    '*' => 'This field must not me more than %max% characters'
                ),
                array('%max%' => $this->_maxLength),
                $this->_maxLength
            ));
        }
        
        if($this->_pattern !== null && !filter_var(
            $value, FILTER_VALIDATE_REGEXP, 
            array('options' => array('regexp' => $this->_pattern))
        )) {
            $node->addError('pattern', $this->_handler->_('The value entered is invalid'));
        }
        
        return $this->_finalize($node, $value);
    }
}
