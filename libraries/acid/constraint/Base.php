<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\acid\constraint;

use df;
use df\core;
use df\acid;

abstract class Base implements IConstraint {
    
    public function getExpectation() {
        return null;
    }
}
