<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\app\_nodes;

use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;
use df\arch;

class TaskBuild extends arch\node\Task
{
    public function execute(): void
    {
        $this->ensureDfSource();
        Cli::newLine();


        // Prepare arguments
        Cli::$command
            ->addArgument('-force|f', 'Force compilation')
            ->addArgument('-dev|d', 'Build without compilation');

        // Setup controller
        $handler = Genesis::$build->getHandler();

        if (Cli::$command['dev']) {
            $handler->setCompile(false);
        } elseif (Cli::$command['force']) {
            $handler->setCompile(true);
        }


        $handler->run();
    }
}
