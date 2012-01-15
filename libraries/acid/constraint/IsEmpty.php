<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\constraint;

use df;
use df\core;
use df\acid;

class IsEmpty extends Base {
    
    public function evaluate($value, $message=null) {
        if(is_object($value) && method_exists($value, 'isEmpty')) {
            return $value->isEmpty();
        }
        
        return empty($value);
    }
    
    public function getFailureDescription($value) {
        return gettype($value).' is not empty';
    }
    
    public function getNegatedFailureDescription($value) {
        return gettype($value).' is empty';
    }
}
