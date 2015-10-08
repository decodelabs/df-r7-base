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
use df\aura;

class TaskReinstallDependencies extends arch\task\Action {

    public function execute() {
        $this->runChild('./purge-dependencies', false);
        aura\theme\Manager::getInstance()->installAllDependencies($this->io);
    }
}