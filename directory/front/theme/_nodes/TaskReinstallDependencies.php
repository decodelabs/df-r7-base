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
use df\spur;
use df\fuse;

use DecodeLabs\Terminus\Cli;

class TaskReinstallDependencies extends arch\node\Task
{
    public function execute()
    {
        $this->runChild('./purge-dependencies');
        fuse\Manager::getInstance()->installAllDependencies(Cli::getSession());
    }
}
