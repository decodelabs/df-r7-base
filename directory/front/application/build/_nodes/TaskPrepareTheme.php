<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\build\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPrepareTheme extends arch\node\Task {

    public function execute() {
        $this->io->write('Clearing sass cache...');

        core\fs\Dir::delete(
            $this->application->getLocalStoragePath().'/sass/'.$this->application->getEnvironmentMode().'/'
        );

        $this->io->writeLine(' done');

        $this->runChild('theme/install-dependencies', false);
    }
}