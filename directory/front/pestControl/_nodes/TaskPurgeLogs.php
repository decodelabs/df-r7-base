<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\pestControl\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPurgeLogs extends arch\node\Task
{
    const SCHEDULE = '0 4 30 * *';
    const SCHEDULE_AUTOMATIC = true;

    public function execute()
    {
        $this->runChild('pest-control/purge-error-logs');
        $this->runChild('pest-control/purge-miss-logs');
        $this->runChild('pest-control/purge-access-logs');
    }
}
