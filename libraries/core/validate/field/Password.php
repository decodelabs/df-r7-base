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


// Options
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

    public function shouldCheckStrength(bool $flag=null) {
        if($flag !== null) {
            $this->_checkStrength = $flag;
            return $this;
        }

        return $this->_checkStrength;
    }

    public function shouldHash(bool $flag=null) {
        if($flag !== null) {
            $this->_shouldHash = $flag;
            return $this;
        }

        return $this->_shouldHash;
    }



// Validate
    public function validate() {
        // Sanitize
        $this->_setDefaultMinLength(self::DEFAULT_MIN_LENGTH);
        $value = $this->_sanitizeValue($this->data->getValue());

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        $this->_validateMinLength($value, $length);

        if($this->data->hasErrors()) {
            return null;
        }

        if($this->_checkStrength && $this->_minStrength > 0) {
            $analyzer = new flex\PasswordAnalyzer($value, df\Launchpad::$app->getPassKey());

            if($analyzer->getStrength() < $this->_minStrength) {
                $this->addError('strength', $this->validator->_(
                    'This password is not strong enough - consider using numbers, capitals and more characters'
                ));
            }
        }


        if($this->_matchField) {
            $data = $this->validator->getCurrentData();
            $matchNode = $data->{$this->_matchField};

            if($matchNode->getValue() != $value) {
                $matchNode->addError('invalid', $this->validator->_(
                    'Your passwords do not match'
                ));
            }
        }



        // Finalize
        $value = $this->_applyCustomValidator($value);
        $this->_applyExtension($value);

        if($this->_shouldHash) {
            $value = core\crypt\Util::passwordHash($value, df\Launchpad::$app->getPassKey());
        }

        return $value;
    }
}
