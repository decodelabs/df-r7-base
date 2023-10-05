<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\daemons\_nodes;

use DecodeLabs\Terminus as Cli;
use df\arch;
use df\halo;

class TaskRemote extends arch\node\Task
{
    use TDaemonTask;

    public function prepareArguments(): array
    {
        return Cli::$command
            ->addArgument('?daemon', 'Daemon name')
            ->addArgument('?command', 'Command to call')
            ->toArray();
    }

    public function execute(): void
    {
        $this->_ensurePrivileges();

        $remote = halo\daemon\Remote::factory($this->request['daemon']);
        $remote->setCliSession(Cli::getSession());
        $remote->sendCommand($this->request['command']);
    }
}
