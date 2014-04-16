<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Integer extends Base implements core\validate\IIntegerField {
    
    use core\validate\TSanitizingField;
    use core\validate\TRangeField;
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        $options = array('flags' => FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX);

        if(!filter_var($value, FILTER_VALIDATE_INT, $options) && $value !== '0') {
            $this->_applyMessage($node, 'invalid', $this->_handler->_(
                'This is not a valid number'
            ));
        } else {
            $value = (int)$value;
        }
        
        $this->_validateRange($node, $value);
        return $this->_finalize($node, $value);
    }
}
