<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\migrate\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;

class TaskUpdateTaskTables extends arch\node\Task {

    public function execute() {
        $this->ensureDfSource();

        $this->runChild('axis/rebuild-table?unit=task/invoke&delete=true');
        $this->runChild('axis/rebuild-table?unit=task/schedule&delete=true');
        $this->runChild('axis/rebuild-table?unit=task/queue&delete=true');

        if($this->application->isDevelopment()) {
            $this->runChild('application/build?dev');
        } else {
            $this->runChild('application/build');
        }
    }
}