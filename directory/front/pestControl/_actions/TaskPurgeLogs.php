<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\pestControl\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPurgeLogs extends arch\task\Action {
    
    const SCHEDULE = '0 16 * * *';
    const SCHEDULE_AUTOMATIC = true;
    const THRESHOLD = '-2 months';

    public function execute() {
        $this->runChild('pest-control/purge-error-logs');
        $this->runChild('pest-control/purge-miss-logs');
        $this->runChild('pest-control/purge-access-logs');
    }
}