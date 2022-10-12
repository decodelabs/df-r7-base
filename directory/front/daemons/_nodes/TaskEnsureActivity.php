<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\daemons\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;

use DecodeLabs\Terminus as Cli;

class TaskEnsureActivity extends arch\node\Task
{
    use TDaemonTask;

    public function execute(): void
    {
        if (!$hasRestarted = $this->_hasRestarted()) {
            Cli::{'yellow'}('Looking up daemon list: ');
        }

        $daemons = halo\daemon\Base::loadAll();

        foreach ($daemons as $name => $daemon) {
            if (!$daemon::AUTOMATIC || $daemon::TEST_MODE) {
                unset($daemons[$name]);
                continue;
            }
        }

        if (!$hasRestarted) {
            Cli::success('found '.count($daemons));
        }

        if (empty($daemons)) {
            return;
        }

        $this->_ensurePrivileges();

        foreach ($daemons as $name => $daemon) {
            $remote = halo\daemon\Remote::factory($daemon);
            $remote->setCliSession(Cli::getSession());
            $remote->nudge();
        }
    }
}
