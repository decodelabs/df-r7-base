<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\build\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskClearNodeCache extends arch\action\Task {

    public function execute() {
        $this->io->write('Clearing node cache...');
        $dir = new core\fs\Dir($this->application->getLocalStoragePath().'/node');

        if($dir->exists()) {
            $dir->moveTo($this->application->getLocalStoragePath().'/node-old', 'node-'.time());
        }

        core\fs\Dir::delete($this->application->getLocalStoragePath().'/node-old');
        $this->io->writeLine(' done');
    }
}