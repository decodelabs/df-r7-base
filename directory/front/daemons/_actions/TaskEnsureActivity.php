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

class TaskEnsureActivity extends arch\task\Action {
    
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

        $this->response->writeLine(' found '.count($daemons).' to keep running');

        if(empty($daemons)) {
            return;
        }

        foreach($daemons as $name => $daemon) {
            $remote = halo\daemon\Remote::factory($daemon);
            $remote->setMultiplexer($this->response);
            $remote->nudge();
        }
    }
}