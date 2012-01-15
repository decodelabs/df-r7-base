<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\batch;

use df;
use df\core;
use df\acid;

abstract class Base implements IBatch {
    
    use acid\TAssert;
    
    public function run() {
        $testList = $this->_getTestMethods();
        
        $return = null;
        
        foreach($testList as $name => $method) {
            $return = $method->invokeArgs($this, [$return]);
        }
    }
    
    public function evaluate($actual, acid\constraint\IConstraint $constraint, $message=null) {
        if(!$constraint->evaluate($actual)) {
            $description = 'Assertion failed: '.$constraint->getFailureDescription($actual);
            
            if($message !== null) {
                $description = $description."\n".$message;
            }
            
            throw new ExpectationFailedException(
                $description, $actual, $constraint->getExpectation()
            );
        }
    }
    
    private function _getTestMethods() {
        $ref = new \ReflectionClass($this);
        $output = array();
        
        foreach($ref->getMethods() as $method) {
            $name = $method->getName();
            
            if(preg_match('/^test[A-Z]/', $name)) {
                $output[$name] = $method;
            }
        }
        
        return $output;
    }
}
