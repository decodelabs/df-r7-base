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
    protected $_minWordLength = null;
    protected $_maxWordLength = null;
  

// Pattern
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


// Word length
    public function setMinWordLength($length) {
        if($length !== null) {
            $length = (int)$length;

            if(empty($length)) {
                $length = 0;
            }

            if($length < 0) {
                $length = 0;
            }
        }
        
        $this->_minWordLength = $length;
        return $this;
    }
    
    public function getMinWordLength() {
        return $this->_minWordLength;
    }

    public function setMaxWordLength($length) {
        if($length !== null) {
            $length = (int)$length;

            if(empty($length)) {
                $length = 0;
            }

            if($length < 0) {
                $length = 0;
            }
        }

        $this->_maxWordLength = $length;
        return $this;
    }

    public function getMaxWordLength() {
        return $this->_maxWordLength;
    }
    
    
// Validate
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);
        
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        
        $this->_validateMinLength($node, $value, $length);
        $this->_validateMaxLength($node, $value, $length);

        if($this->_minWordLength !== null || $this->_maxWordLength !== null) {
            $wordCount = core\string\Manipulator::countWords($value);

            if($this->_minWordLength !== null && $wordCount < $this->_minWordLength) {
                $this->_applyMessage($node, 'minWordLength', $this->_handler->_(
                    [
                        'n = 1' => 'This field must contain at least %min% word',
                        '*' => 'This field must contain at least %min% words'
                    ],
                    ['%min%' => $this->_minWordLength],
                    $this->_minWordLength
                ));
            }

            if($this->_maxWordLength !== null && $wordCount > $this->_maxWordLength) {
                $this->_applyMessage($node, 'maxWordLength', $this->_handler->_(
                    [
                        'n = 1' => 'This field must not me more than %max% word',
                        '*' => 'This field must not me more than %max% words'
                    ],
                    ['%max%' => $this->_maxWordLength],
                    $this->_maxWordLength
                ));
            }
        }
        
        if($this->_pattern !== null && !filter_var(
            $value, FILTER_VALIDATE_REGEXP, 
            ['options' => ['regexp' => $this->_pattern]]
        )) {
            $node->addError('pattern', $this->_handler->_('The value entered is invalid'));
        }
        
        return $this->_finalize($node, $value);
    }
}
