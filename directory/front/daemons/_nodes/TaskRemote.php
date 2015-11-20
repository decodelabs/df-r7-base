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

class TaskRemote extends arch\node\Task {

    use TDaemonTask;

    public function execute() {
        $this->_ensurePrivileges();

        $remote = halo\daemon\Remote::factory($this->request['daemon']);
        $remote->setMultiplexer($this->io);
        $remote->sendCommand($this->request['command']);
    }
}