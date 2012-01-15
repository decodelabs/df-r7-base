<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\constraint;

use df;
use df\core;
use df\acid;

class Not extends Base {
    
    protected $_constraint;
    
    public function __construct(IConstraint $constraint) {
        $this->_constraint = $constraint;
    }
    
    public function evaluate($value, $message=null) {
        return !$this->_constraint->evaluate($value, $message);
    }
    
    public function getFailureDescription($value) {
        return $this->_constraint->getNegatedFailureDescription($value);
    }
    
    public function getNegatedFailureDescription($value) {
        return $this->_constraint->getFailureDescription($value);
    }
}
