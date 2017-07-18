<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskClearNodeCache extends arch\node\Task implements arch\node\IBuildTaskNode {

    public function execute() {
        $this->io->write('Clearing node cache...');
        $dir = new core\fs\Dir($this->app->getLocalDataPath().'/node');

        if($dir->exists()) {
            $dir->moveTo($this->app->getLocalDataPath().'/node-old', 'node-'.time());
        }

        core\fs\Dir::delete($this->app->getLocalDataPath().'/node-old');
        $this->io->writeLine(' done');
    }
}
