<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\composer\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;

class TaskInstall extends arch\node\Task
{
    public function execute(): void
    {
        $file = Atlas::file(Genesis::$hub->getApplicationPath().'/composer.json');

        if (!$file->exists()) {
            $this->runChild('composer/init?no-update');
        }

        $args = [];

        if (!Genesis::$environment->isDevelopment()) {
            $args[] = '--no-dev';
        }

        Systemic::$process->newLauncher('composer install', $args)
            ->setWorkingDirectory(Genesis::$hub->getApplicationPath())
            ->setBroker(Cli::getSession()->getBroker())
            ->launch();
    }
}
