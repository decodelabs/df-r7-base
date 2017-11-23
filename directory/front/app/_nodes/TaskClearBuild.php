<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\app\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskClearBuild extends arch\node\Task {

    public function execute() {
        $this->ensureDfSource();

        $appPath = $this->app->path;
        $envId = $this->app->envId;

        core\fs\File::delete($appPath.'/data/local/run/active/Run.php');

        $this->runChild('app/purge-builds?all', false);
        core\fs\Dir::delete($appPath.'/data/local/run/');

        core\fs\File::delete($appPath.'/entry/'.$envId.'.testing.php');
        core\fs\File::delete($appPath.'/entry/'.$envId.'.production.php');
    }
}
