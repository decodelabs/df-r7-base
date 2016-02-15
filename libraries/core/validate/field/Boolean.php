<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\flex;

class Boolean extends Base implements core\validate\IBooleanField {

    use core\validate\TRequiredValueField;

    protected $_isRequired = true;
    protected $_forceAnswer = true;

    public function shouldForceAnswer($flag=null) {
        if($flag !== null) {
            $this->_forceAnswer = (bool)$flag;
            return $this;
        }

        return $this->_forceAnswer;
    }

    protected function _prepareRequiredValue($value) {
        return flex\Text::stringToBoolean($value);
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        $isRequired = $this->_isRequiredAfterToggle($node, $value);

        if(!is_bool($value)) {
            if(!$length = strlen($value)) {
                $value = null;

                if($this->_isRequired && $this->_forceAnswer) {
                    $value = false;
                }
            } else {
                if(is_string($value)) {
                    $value = flex\Text::stringToBoolean($value);
                } else {
                    $value = (bool)$value;
                }
            }
        }

        if($isRequired && $value === null) {
            $this->_applyMessage($node, 'required', $this->validator->_(
                'This field requires an answer'
            ));
        } else {
            $this->_checkRequiredValue($node, $value, $isRequired);
        }

        return $this->_finalize($node, $value);
    }
}
