<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\daemons\_nodes;

use DecodeLabs\Terminus as Cli;
use df\arch;

use df\halo;

class TaskRestartAll extends arch\node\Task
{
    use TDaemonTask;

    public function execute(): void
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

        foreach ($daemons as $name => $daemon) {
            $remote = halo\daemon\Remote::factory($daemon);
            $remote->setCliSession(Cli::getSession());

            if ($remote->isRunning()) {
                $remote->restart();
            } else {
                Cli::warning('Daemon ' . $name . ' is not running');
                $remote->start();
            }
        }
    }
}
