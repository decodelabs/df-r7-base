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

    protected $_isRequired = true;

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if(!is_bool($value)) {
            if(!$length = strlen($value)) {
                $value = null;

                if($this->_isRequired) {
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

        return $this->_finalize($node, $value);
    }
}
