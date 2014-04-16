<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Email extends Base implements core\validate\IEmailField {
    
    use core\validate\TStorageAwareField;
    use core\validate\TUniqueCheckerField;

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        $value = strtolower($value);
        $value = str_replace(array(' at ', ' dot '), array('@', '.'), $value);
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        
        if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->_applyMessage($node, 'invalid', $this->_handler->_(
                'This is not a valid email address'
            ));
        }
        
        $this->_validateUnique($node, $value);
        return $this->_finalize($node, $value);
    }
}
