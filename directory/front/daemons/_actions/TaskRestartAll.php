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

class TaskRestartAll extends arch\task\Action {
    
    use TDaemonTask;
    
    public function execute() {
        $this->task->shouldCaptureBackgroundTasks(true);
        $this->response->write('Looking up daemon list...');

        $daemons = halo\daemon\Base::loadAll();

        foreach($daemons as $name => $daemon) {
            if(!$daemon::AUTOMATIC || $daemon::TEST_MODE) {
                unset($daemons[$name]);
            }
        }

        $this->response->writeLine(' found '.count($daemons).' to restart');

        if(empty($daemons)) {
            return;
        }

        foreach($daemons as $name => $daemon) {
            $remote = halo\daemon\Remote::factory($daemon);

            if($remote->isRunning()) {
                $remote->setMultiplexer($this->response);
                $remote->restart();
            } else {
                $this->response->writeLine('Daemon '.$name.' is not running');
            }
        }
    }
}