<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\app\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;
use df\spur;

use DecodeLabs\Terminus as Cli;

class TaskUpdate extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();
        $this->runChild('git/update?package=app&no-build');
        $this->runChild('composer/install');
        Cli::newLine();

        $this->launch('app/build');
    }
}
