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

use DecodeLabs\Systemic;
use DecodeLabs\Atlas;
use DecodeLabs\Terminus\Cli;

class TaskInstall extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute()
    {
        $file = Atlas::$fs->file($this->app->path.'/composer.json');

        if (!$file->exists()) {
            $this->runChild('composer/init?no-update');
        }

        $args = [];

        if (!$this->app->isDevelopment()) {
            $args[] = '--no-dev';
        }

        Systemic::$process->newLauncher('composer install', $args)
            ->setWorkingDirectory($this->app->path)
            ->setIoBroker(Cli::getSession()->getBroker())
            ->launch();
    }
}
