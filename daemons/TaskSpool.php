<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\daemons;

use DecodeLabs\Terminus as Cli;

use df\halo;

class TaskSpool extends halo\daemon\Base
{
    public const AUTOMATIC = true;

    protected function _setup()
    {
        $this->events->bindTimerOnce('spoolNow', 1, [$this, 'spool']);
        $this->events->bindTimer('spool', 60, [$this, 'spool']);
    }

    public function spool()
    {
        $this->task->launch(
            'tasks/spool',
            Cli::getSession(),
            null,
            false,
            false
        );
    }
}
