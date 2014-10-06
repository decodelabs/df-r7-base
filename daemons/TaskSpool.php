<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\daemons;

use df;
use df\core;
use df\apex;
use df\halo;

class TaskSpool extends halo\daemon\Base {
    
    const AUTOMATIC = true;
    
    protected function _setup() {
        $this->events->bindTimerOnce('spoolNow', 1, [$this, 'spool']);
        $this->events->bindTimer('spool', 60, [$this, 'spool']);
    }

    public function spool() {
        $this->task->launch('manager/spool', $this->io);        
    }
}