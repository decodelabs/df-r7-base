<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid;

use df;
use df\core;
use df\acid;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}

class ExpectationFailedException extends RuntimeException implements core\IDumpable {
    
    protected $_value;
    protected $_expectation;
    
    public function __construct($message, $value, $expectation=null) {
        $this->_value = $value;
        $this->_expectation = $expectation;
        parent::__construct($message);
    }
    
    public function getDumpProperties() {
        if($this->_value !== null && $this->_expectation !== null) {
            return [
                'value' => $this->_value,
                'expectation' => $this->_expectation
            ];
        }
        
        if($this->_value !== null) {
            return $this->_value;
        }
        
        if($this->_expectation !== null) {
            return $this->_expectation;
        }
    }
}


// Interface
interface IAssert {
    public function assertEquals($expected, $actual, $message=null);
    public function assertNotEquals($expected, $actual, $message=null);
    
    public function assertNull($actual, $message=null);
    public function assertNotNull($actual, $message=null);
    public function assertTrue($actual, $message=null);
    public function assertFalse($actual, $message=null);
    
    public function assertEmpty($actual, $message=null);
    public function assertNotEmpty($actual, $message=null);
    
    public function evaluate($actual, acid\constraint\IConstraint $constraint, $message=null);
}


interface IBatch extends IAssert {
    
    public function run();
}


interface IResult {
    public function addBatch(IBatch $batch);
    public function hasBatch($batch);
    public function hasBatchFailed($batch);
    public function getBatches();
    
    public function registerSuccess(IBatch $batch, $testName);
    public function registerFailure(IBatch $batch, $testName, \Exception $e);
}
