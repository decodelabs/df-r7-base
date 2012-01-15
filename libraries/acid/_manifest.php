<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid;

use df;
use df\core;
use df\acid;



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

