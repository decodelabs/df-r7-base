<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Password extends Base {
    
    protected $_minLength = 6;
    protected $_matchField = null;
    
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
    
    
    public function setMatchField($field) {
        $this->_matchField = $field;
        return $this;
    }
    
    public function getMatchField() {
        return $this->_matchField;
    }
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        
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
            
            return $value;
        }
        
        $value = $this->_applyCustomValidator($node, $value);
        $analyzer = new core\string\PasswordAnalyzer($value, df\Launchpad::$application->getPassKey());
        
        if($analyzer->getStrength() < 25) {
            $node->addError('strength', $this->_handler->_(
                'This password is not strong enough - consider using numbers, capitals and more characters'
            ));
        }
        
        
        if($this->_matchField) {
            $data = $this->_handler->getCurrentData();
            $matchNode = $data->{$this->_matchField};
            
            if($matchNode->getValue() != $value) {
                $matchNode->addError('invalid', $this->_handler->_(
                    'Your passwords do not match'
                ));
            }
        }
        
        return $analyzer->getHash();
    }
}
