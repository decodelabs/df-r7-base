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

class TaskEnsureActivity extends arch\node\Task
{
    use TDaemonTask;

    public function execute()
    {
        if (!$hasRestarted = $this->_hasRestarted()) {
            $this->io->write('Looking up daemon list...');
        }

        $daemons = halo\daemon\Base::loadAll();

        foreach ($daemons as $name => $daemon) {
            if (!$daemon::AUTOMATIC || $daemon::TEST_MODE) {
                unset($daemons[$name]);
                continue;
            }
        }

        if (!$hasRestarted) {
            $this->io->writeLine(' found '.count($daemons).' to keep running');
        }

        if (empty($daemons)) {
            return;
        }

        $this->_ensurePrivileges();

        foreach ($daemons as $name => $daemon) {
            $remote = halo\daemon\Remote::factory($daemon);
            $remote->setMultiplexer($this->io);
            $remote->nudge();
        }
    }
}
