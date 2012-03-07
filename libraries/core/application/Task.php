<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;

class Task extends Base {
    
    const RUN_MODE = 'Task';
    
    
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
