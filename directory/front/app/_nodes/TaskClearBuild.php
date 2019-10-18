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

use DecodeLabs\Atlas;

class TaskClearBuild extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        $appPath = $this->app->path;
        $envId = $this->app->envId;

        Atlas::$fs->deleteFile($appPath.'/data/local/run/active/Run.php');

        $this->runChild('app/purge-builds?all');
        Atlas::$fs->deleteDir($appPath.'/data/local/run/');

        Atlas::$fs->deleteFile($appPath.'/entry/'.$envId.'.testing.php');
        Atlas::$fs->deleteFile($appPath.'/entry/'.$envId.'.production.php');
    }
}
