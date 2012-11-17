<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Password extends Base implements core\validate\IPasswordField {
    
    use core\validate\TMinLengthField;
    
    protected $_matchField = null;
    
    public function setMatchField($field) {
        $this->_matchField = $field;
        return $this;
    }
    
    public function getMatchField() {
        return $this->_matchField;
    }
    
    public function validate(core\collection\IInputTree $node) {
        $this->_setDefaultMinLength(6);
        $value = $node->getValue();
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        $this->_validateMinLength($node, $value, $length);

        if($node->hasErrors()) {
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
