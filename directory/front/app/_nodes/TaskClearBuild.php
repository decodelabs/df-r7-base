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

        Atlas::deleteFile($appPath.'/data/local/run/active/Run.php');

        $this->runChild('app/purge-builds?all');
        Atlas::deleteDir($appPath.'/data/local/run/');

        Atlas::deleteFile($appPath.'/entry/'.$envId.'.testing.php');
        Atlas::deleteFile($appPath.'/entry/'.$envId.'.production.php');
    }
}
