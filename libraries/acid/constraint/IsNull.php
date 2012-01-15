<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\constraint;

use df;
use df\core;
use df\acid;

class IsNull extends Base {
    
    public function evaluate($value) {
        return $value === null;
    }
    
    public function getFailureDescription($value) {
        return gettype($value).' is not null';
    }
    
    public function getNegatedFailureDescription($value) {
        return gettype($value).' is null';
    }
}
