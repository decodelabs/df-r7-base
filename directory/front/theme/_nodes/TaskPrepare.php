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

use DecodeLabs\Terminus as Cli;

class TaskPrepare extends arch\node\Task
{
    public function execute(): void
    {
        $this->runChild('theme/install-dependencies');
        Cli::newLine();

        $this->runChild('theme/rebuild-sass');
    }
}
