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
    public const SCHEDULE = '30 4 * * *';
    public const SCHEDULE_AUTOMATIC = true;

    public function execute(): void
    {
        $this->runChild('pest-control/purge-error-logs');
        $this->runChild('pest-control/purge-miss-logs');
        $this->runChild('pest-control/purge-access-logs');
    }
}
