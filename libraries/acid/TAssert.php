<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid;

use df;
use df\core;
use df\acid;

trait TAssert {
    
// Equals
    public function assertEquals($expected, $actual, $message=null) {
        $this->evaluate(
            $actual, $this->_equals($expected), $message
        );
    }
    
    public function assertNotEquals($expected, $actual, $message=null) {
        $this->evaluate(
            $actual, $this->_not($this->_equals($expected)), $message
        );
    }
    
    
// Null
    public function assertNull($actual, $message=null) {
        $this->evaluate(
            $actual, $this->_isNull(), $message
        );
    }
    
    public function assertNotNull($actual, $message=null) {
        $this->evaluate(
            $actual, $this->_not($this->_isNull()), $message
        );
    }
    
    
// Boolean
    public function assertTrue($actual, $message=null) {
        $this->evaluate(
            $actual, $this->_boolean(true), $message
        );
    }
    
    public function assertFalse($actual, $message=null) {
        $this->evaluate(
            $actual, $this->_boolean(false), $message
        );
    }
    
    
// Empty
    public function assertEmpty($actual, $message=null) {
        $this->evaluate(
            $actual, $this->_isEmpty(), $message
        );
    }
    
    public function assertNotEmpty($actual, $message=null) {
        $this->evaluate(
            $actual, $this->_not($this->_isEmpty()), $message
        );
    }
    
    
// Constraints
    private function _equals($expected) {
        return new acid\constraint\Equals($expected);
    }
    
    private function _boolean($expected) {
        return new acid\constraint\Boolean($expected);
    }
    
    private function _isNull() {
        return new acid\constraint\IsNull();
    }
    
    private function _isEmpty() {
        return new acid\constraint\IsEmpty();
    }
    
    
    
// Logic constraints
    private function _not(acid\constraint\IConstraint $constraint) {
        return new acid\constraint\Not($constraint);
    }
}
