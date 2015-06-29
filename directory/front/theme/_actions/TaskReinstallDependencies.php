<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\spur;

class TaskReinstallDependencies extends arch\task\Action {
    
    public function execute() {
        $this->runChild('./purge-dependencies', false);
        $this->runChild('./install-dependencies', false);
    }
}