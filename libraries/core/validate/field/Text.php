<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Text extends Base implements core\validate\ITextField {
    
    use core\validate\TSanitizingField;
    use core\validate\TMinLengthField;
    use core\validate\TMaxLengthField;

    protected $_pattern = null;
    
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
    
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);
        
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        
        $this->_validateMinLength($node, $value, $length);
        $this->_validateMaxLength($node, $value, $length);

        
        if($this->_pattern !== null && !filter_var(
            $value, FILTER_VALIDATE_REGEXP, 
            array('options' => array('regexp' => $this->_pattern))
        )) {
            $node->addError('pattern', $this->_handler->_('The value entered is invalid'));
        }
        
        return $this->_finalize($node, $value);
    }
}
