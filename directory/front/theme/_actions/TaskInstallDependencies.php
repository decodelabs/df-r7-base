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
use df\aura;
use df\spur;

class TaskInstallDependencies extends arch\task\Action {

    public function execute() {
        if(!is_dir($this->application->getLocalStoragePath().'/theme/dependencies/')) {
            $this->runChild('./purge-dependencies', false);
        }

        aura\theme\Manager::getInstance()->installAllDependencies($this->io);
    }
}