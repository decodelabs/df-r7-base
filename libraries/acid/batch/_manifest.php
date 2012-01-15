<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\batch;

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


// Interfaces
interface IBatch extends acid\IAssert {
    
    public function run();
}
