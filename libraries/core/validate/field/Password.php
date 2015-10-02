<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\flex;

class Password extends Base implements core\validate\IPasswordField {

    use core\validate\TMinLengthField;

    const DEFAULT_MIN_LENGTH = 6;

    protected $_matchField = null;
    protected $_minStrength = 18;
    protected $_checkStrength = true;
    protected $_shouldHash = true;

    public function setMatchField($field) {
        $this->_matchField = $field;
        return $this;
    }

    public function getMatchField() {
        return $this->_matchField;
    }

    public function setMinStrength($strength) {
        $this->_minStrength = (int)$strength;
        return $this;
    }

    public function getMinStrength() {
        return $this->_minStrength;
    }

    public function shouldCheckStrength($flag=null) {
        if($flag !== null) {
            $this->_checkStrength = (bool)$flag;
            return $this;
        }

        return $this->_checkStrength;
    }

    public function shouldHash($flag=null) {
        if($flag !== null) {
            $this->_shouldHash = (bool)$flag;
            return $this;
        }

        return $this->_shouldHash;
    }


    public function validate(core\collection\IInputTree $node) {
        $this->_setDefaultMinLength(self::DEFAULT_MIN_LENGTH);

        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        $this->_validateMinLength($node, $value, $length);

        if($node->hasErrors()) {
            return $value;
        }

        $value = $this->_applyCustomValidator($node, $value);

        if($this->_checkStrength && $this->_minStrength > 0) {
            $analyzer = new flex\PasswordAnalyzer($value, df\Launchpad::$application->getPassKey());

            if($analyzer->getStrength() < $this->_minStrength) {
                $this->_applyMessage($node, 'strength', $this->validator->_(
                    'This password is not strong enough - consider using numbers, capitals and more characters'
                ));
            }
        }


        if($this->_matchField) {
            $data = $this->validator->getCurrentData();
            $matchNode = $data->{$this->_matchField};

            if($matchNode->getValue() != $value) {
                $this->_applyMessage($matchNode, 'invalid', $this->validator->_(
                    'Your passwords do not match'
                ));
            }
        }

        if($this->_shouldHash) {
            $value = core\crypt\Util::passwordHash($value, df\Launchpad::$application->getPassKey());
        }

        return $value;
    }
}
