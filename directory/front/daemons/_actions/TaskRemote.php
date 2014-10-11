<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\daemons\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;

class TaskRemote extends arch\task\Action {
    
    use TDaemonTask;

    public function execute() {
        $this->_ensurePrivileges();

        $remote = halo\daemon\Remote::factory($this->request->query['daemon']);
        $remote->setMultiplexer($this->io);
        $remote->sendCommand($this->request->query['command']);
    }
}