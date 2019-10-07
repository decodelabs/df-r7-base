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

class TaskRemote extends arch\node\Task
{
    use TDaemonTask;

    public function extractCliArguments(core\cli\ICommand $command)
    {
        $args = [];

        foreach ($command->getArguments() as $arg) {
            if (!$arg->isOption()) {
                $args[] = (string)$arg;
            }
        }

        if (isset($args[0])) {
            $this->request->query->daemon = $args[0];
        }

        if (isset($args[1])) {
            $this->request->query->command = $args[1];
        }
    }

    public function execute()
    {
        $this->_ensurePrivileges();

        $remote = halo\daemon\Remote::factory($this->request['daemon']);
        $remote->setMultiplexer($this->io);
        $remote->sendCommand($this->request['command']);
    }
}
