<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPrepare extends arch\node\Task implements arch\node\IBuildTaskNode {

    public function execute() {
        $this->io->write('Clearing sass cache...');

        core\fs\Dir::delete(
            $this->application->getLocalStoragePath().'/sass/'.$this->application->getEnvironmentMode().'/'
        );

        $this->io->writeLine(' done');

        $this->runChild('theme/install-dependencies', false);
    }
}