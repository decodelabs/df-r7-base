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

class TaskInstall extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute()
    {
        $file = new core\fs\File($this->app->path.'/composer.json');

        if (!$file->exists()) {
            $this->runChild('composer/init?no-update');
        }

        $args = [];

        if ($this->app->isProduction()) {
            $args[] = '--no-dev';
        }

        $this->io->writeLine('Calling: composer install '.implode(' ', $args));
        $this->io->indent();

        halo\process\launcher\Base::factory('composer install', $args)
            ->setWorkingDirectory($this->app->path)
            ->setMultiplexer($this->io)
            ->launch();

        $this->io->outdent();
    }
}
