<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;

class Cli extends Base {
    
    const RUN_MODE = 'Cli';
    
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();
        
        core\stub();
        
        df\Launchpad::benchmark();
    }
    
    public function launchPayload($payload) {
        core\stub();
    }
}
