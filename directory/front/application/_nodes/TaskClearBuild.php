<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskClearBuild extends arch\node\Task {

    public function execute() {
        $appPath = $this->application->getApplicationPath();
        $envId = $this->application->getEnvironmentId();

        $this->runChild('application/purge-builds?all', false);

        $this->io->writeLine('Deleting testing and production entry files');
        core\fs\File::delete($appPath.'/entry/'.$envId.'.testing.php');
        core\fs\File::delete($appPath.'/entry/'.$envId.'.production.php');
    }
}