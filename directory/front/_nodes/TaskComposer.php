<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\_nodes;

use df\arch;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;

class TaskComposer extends arch\node\Task
{
    public function execute()
    {
        $args = $_SERVER['argv'];
        array_shift($args);
        array_shift($args);

        $next = $args[0] ?? null;

        switch ($next) {
            case 'install':
                if (!Genesis::$environment->isDevelopment()) {
                    $args[] = '--no-dev';
                }
                break;
        }

        $file = Atlas::file(Genesis::$hub->getApplicationPath().'/composer.json');

        if (!$file->exists()) {
            $this->runChild('composer/init?no-update');
        }

        Systemic::$process->newLauncher('composer', $args)
            ->setWorkingDirectory(Genesis::$hub->getApplicationPath())
            ->setBroker(Cli::getSession()->getBroker())
            ->launch();
    }
}
