<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Boolean extends Base {

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        
        if(!$length = strlen($value)) {
            $value = null;
            
            if($this->_isRequired) {
                $value = false;
            }
        } else {
            if(is_string($value)) {
                $value = strtolower($value);
                
                switch($value) {
                    case 'false':
                    case 'no':
                    case 'n':
                    case '0':
                        $value = false;
                        break;
                        
                    default:
                        $value = true;
                        break;
                }
            } else {
                $value = (bool)$value;
            }
        }

        return $this->_finalize($node, $value);
    }
}
