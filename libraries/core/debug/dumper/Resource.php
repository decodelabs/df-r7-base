<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Resource implements core\debug\IDump {
    
    use core\TStringProvider;
    
    public function __construct($resource) {
        core\qDump($resource);
    }
    
    public function toString() {
        core\qDump($this);
    }
}
