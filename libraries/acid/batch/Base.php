<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\batch;

use df;
use df\core;
use df\acid;

abstract class Base implements acid\IBatch {
    
    use acid\TAssert;
    
    public function run(acid\IResult $result=null) {
        if($result === null) {
            $result = new acid\Result();
        }
        
        if($result->hasBatch($this)) {
            return $result;
        }
        
        $result->addBatch($this);
        
        $testList = $this->_getTestMethods();
        $return = [];
        $lastName = null;
        
        foreach($testList as $name => $method) {
            try {
                if(isset($lastName)) {
                    $args = [$return[$lastName]];
                } else {
                    $args = [];
                }
                
                $lastName = $name;
                
                $return[$name] = $method->invokeArgs($this, $args);
                $result->registerSuccess($this, $name);
            } catch(\Exception $e) {
                $result->registerFailure($this, $name, $e);
            }
        }
        
        return $result;
    }
    
    public function evaluate($actual, acid\constraint\IConstraint $constraint, $message=null) {
        if(!$constraint->evaluate($actual)) {
            $description = 'Assertion failed: '.$constraint->getFailureDescription($actual);
            
            if($message !== null) {
                $description = $description."\n".$message;
            }
            
            throw new acid\ExpectationFailedException(
                $description, $actual, $constraint->getExpectation()
            );
        }
    }
    
    private function _getTestMethods() {
        $ref = new \ReflectionClass($this);
        $output = [];
        
        foreach($ref->getMethods() as $method) {
            $name = $method->getName();
            
            if(preg_match('/^test[A-Z]/', $name)) {
                $output[$name] = $method;
            }
        }
        
        return $output;
    }
}
