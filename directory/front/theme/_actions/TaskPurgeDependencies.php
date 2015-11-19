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

class TaskPurgeDependencies extends arch\action\Task {

    public function execute() {
        $this->io->write('Purging theme dependencies...');

        $vendorPath = df\Launchpad::$application->getApplicationPath().'/assets/vendor/';
        core\fs\Dir::delete($vendorPath);
        $themePath = df\Launchpad::$application->getLocalStoragePath().'/theme/dependencies/';
        core\fs\Dir::delete($themePath);

        $this->io->writeLine(' done');
    }
}