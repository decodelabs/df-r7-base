<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\theme\_nodes;

use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskPrepare extends arch\node\Task
{
    public function execute(): void
    {
        $this->runChild('theme/install-dependencies');
        Cli::newLine();

        $this->runChild('theme/rebuild-sass');
    }
}
