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

class TaskPurgeLogs extends arch\action\Task {

    const SCHEDULE = '0 16 * * *';
    const SCHEDULE_AUTOMATIC = true;

    public function execute() {
        $this->runChild('pest-control/purge-error-logs', false);
        $this->runChild('pest-control/purge-miss-logs', false);
        $this->runChild('pest-control/purge-access-logs', false);
    }
}