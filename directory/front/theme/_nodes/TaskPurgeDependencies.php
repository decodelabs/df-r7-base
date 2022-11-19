<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\theme\_nodes;

use DecodeLabs\Atlas;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\fuse;

class TaskPurgeDependencies extends arch\node\Task
{
    public function execute(): void
    {
        Cli::{'yellow'}('Purging theme dependencies: ');

        Atlas::deleteDir(fuse\Manager::getAssetPath());
        Atlas::deleteDir(fuse\Manager::getManifestCachePath());

        Cli::success('done');
    }
}
