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
use df\halo;

class TaskBower extends arch\node\Task {

    public function execute() {
        if(!core\fs\File::iFileExists($this->application->getApplicationPath().'/bower.json')) {
            return;
        }

        $this->io->writeLine('Calling bower');

        halo\process\launcher\Base::factory('bower install')
            ->setWorkingDirectory($this->application->getApplicationPath())
            ->setMultiplexer($this->io)
            ->launch();
    }
}