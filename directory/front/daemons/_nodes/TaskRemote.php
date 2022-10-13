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

class TaskRemote extends arch\node\Task
{
    use TDaemonTask;

    public function prepareArguments(): array
    {
        Cli::getCommandDefinition()
            ->addArgument('?daemon', 'Daemon name')
            ->addArgument('?command', 'Command to call');

        return Cli::prepareArguments();
    }

    public function execute(): void
    {
        $this->_ensurePrivileges();

        $remote = halo\daemon\Remote::factory($this->request['daemon']);
        $remote->setCliSession(Cli::getSession());
        $remote->sendCommand($this->request['command']);
    }
}
