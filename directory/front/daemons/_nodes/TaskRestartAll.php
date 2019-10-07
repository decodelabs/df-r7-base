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

class TaskRestartAll extends arch\node\Task
{
    use TDaemonTask;

    public function execute()
    {
        $daemons = halo\daemon\Base::loadAll();

        foreach ($daemons as $name => $daemon) {
            if (!$daemon::AUTOMATIC || $daemon::TEST_MODE) {
                unset($daemons[$name]);
                continue;
            }

            $remote = halo\daemon\Remote::factory($daemon);

            if ($remote->isRunning()) {
                $this->_ensurePrivileges();
            }
        }

        if (empty($daemons)) {
            return;
        }

        $this->task->shouldCaptureBackgroundTasks(true);
        $this->io->indent();

        foreach ($daemons as $name => $daemon) {
            $remote = halo\daemon\Remote::factory($daemon);

            if ($remote->isRunning()) {
                $remote->setMultiplexer($this->io);
                $remote->restart();
            } else {
                $this->io->writeLine('Daemon '.$name.' is not running');
            }
        }

        $this->io->outdent();
    }
}
