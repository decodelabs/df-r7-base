<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\app\_nodes;

use df\arch;

use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

class TaskBuild extends arch\node\Task
{
    public function execute(): void
    {
        $this->ensureDfSource();
        Cli::newLine();


        // Prepare arguments
        Cli::getCommandDefinition()
            ->addArgument('-force|f', 'Force compilation')
            ->addArgument('-dev|d', 'Build without compilation');
        Cli::prepareArguments();


        // Setup controller
        $handler = Genesis::$build->getHandler();

        if (Cli::getArgument('dev')) {
            $handler->setCompile(false);
        } elseif (Cli::getArgument('force')) {
            $handler->setCompile(true);
        }


        $handler->run();
    }
}
