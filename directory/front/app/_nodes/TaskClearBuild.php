<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\app\_nodes;

use df\arch;

use DecodeLabs\Genesis\Build\Handler;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\R7\Genesis\BuildManifest;

class TaskClearBuild extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        // Setup controller
        $handler = new Handler(
            new BuildManifest(Cli::getSession())
        );

        $handler->clear();
    }
}
