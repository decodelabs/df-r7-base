<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Float extends Base implements core\validate\IFloatField {
    
    use core\validate\TSanitizingField;
    use core\validate\TRangeField;    
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        if(false === filter_var($value, FILTER_VALIDATE_FLOAT) && $value !== '0') {
            $this->_applyMessage($node, 'invalid', $this->_handler->_(
                'This is not a valid number'
            ));
        } else {
            $value = (float)$value;
        }
        
        $this->_validateRange($node, $value);
        return $this->_finalize($node, $value);
    }
}
