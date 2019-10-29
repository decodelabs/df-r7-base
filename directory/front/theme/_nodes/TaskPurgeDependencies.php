<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\aura;
use df\fuse;

use DecodeLabs\Terminus\Cli;
use DecodeLabs\Atlas;

class TaskPurgeDependencies extends arch\node\Task
{
    public function execute()
    {
        Cli::{'yellow'}('Purging theme dependencies: ');

        Atlas::$fs->deleteDir(fuse\Manager::getAssetPath());
        Atlas::$fs->deleteDir(fuse\Manager::getManifestCachePath());

        Cli::success('done');
    }
}
