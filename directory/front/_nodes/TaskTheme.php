<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\_nodes;

use DecodeLabs\Genesis;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;
use df\arch;

class TaskTheme extends arch\node\Task
{
    public function execute(): void
    {
        Cli::getCommandDefinition()
            ->addArgument('theme', 'Theme name')
            ->addArgument('command', 'Target command');

        Cli::prepareArguments();
        $appPath = Genesis::$hub->getApplicationPath();

        Systemic::command([
                $appPath . '/vendor/bin/zest',
                Cli::getArgument('command'),
                ...Cli::getPassthroughArguments(
                    'task',
                    'theme',
                    'command',
                    'df-source'
                )
            ])
            ->setWorkingDirectory($appPath . '/themes/' . Cli::getArgument('theme'))
            ->addSignal('SIGINT', 'SIGTERM', 'SIGQUIT')
            ->run();
    }
}
