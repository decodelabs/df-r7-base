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
use df\fuse;

class TaskInstallDependencies extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute()
    {
        $manager = fuse\Manager::getInstance();

        if (!is_dir($manager::getManifestCachePath())) {
            $this->runChild('./purge-dependencies');
        }

        $manager->installAllDependencies($this->io);
    }
}
