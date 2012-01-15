<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\constraint;

use df;
use df\core;
use df\acid;

// Exceptions
interface IException {}


// Interfaces
interface IConstraint {
    public function evaluate($value);
    public function getExpectation();
    public function getFailureDescription($value);
    public function getNegatedFailureDescription($value);
}
