<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\theme\_nodes;

use DecodeLabs\Terminus as Cli;
use df\arch;

use df\fuse;

class TaskReinstallDependencies extends arch\node\Task
{
    public function execute(): void
    {
        $this->runChild('./purge-dependencies');
        fuse\Manager::getInstance()->installAllDependencies(Cli::getSession());
    }
}
