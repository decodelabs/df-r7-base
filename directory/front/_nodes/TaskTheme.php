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
        Cli::$command
            ->addArgument('theme', 'Theme name')
            ->addArgument('command=dev', 'Target command');

        $appPath = Genesis::$hub->getApplicationPath();
        $parts = explode(':', Cli::$command['theme']);
        $theme = array_shift($parts);
        $config = array_shift($parts) ?? 'vite';

        Systemic::command([
                $appPath . '/vendor/bin/zest',
                Cli::$command['command'],
                '--config=' . $config,
                ...Cli::$command->passthrough(
                    'task',
                    'theme',
                    'command',
                    'df-source'
                )
            ])
            ->setWorkingDirectory($appPath . '/themes/' . $theme)
            ->addSignal('SIGINT', 'SIGTERM', 'SIGQUIT')
            ->run();
    }
}
