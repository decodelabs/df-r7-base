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
use df\halo;
use df\flex;

use DecodeLabs\Genesis\Build\Handler;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\R7\Genesis\BuildManifest;

class TaskBuild extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();
        Cli::newLine();


        // Prepare arguments
        Cli::getCommandDefinition()
            ->addArgument('-force|f', 'Force compilation')
            ->addArgument('-dev|d', 'Build without compilation');
        Cli::prepareArguments();


        // Setup controller
        $handler = new Handler(
            new BuildManifest(Cli::getSession())
        );

        if (Cli::getArgument('dev')) {
            $handler->setCompile(false);
        } elseif (Cli::getArgument('force')) {
            $handler->setCompile(true);
        }


        $handler->run();
    }
}
