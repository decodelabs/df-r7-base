<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
    
class TaskClearBuild extends arch\task\Action {

    public function execute() {
        $appPath = $this->application->getApplicationPath();
        $envId = $this->application->getEnvironmentId();

        $this->runChild('application/purge-builds?all');

        $this->io->writeLine('Deleting testing and production entry files');
        core\io\Util::deleteFile($appPath.'/entry/'.$envId.'.testing.php');
        core\io\Util::deleteFile($appPath.'/entry/'.$envId.'.production.php');
    }
}