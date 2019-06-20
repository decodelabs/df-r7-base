<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;

class TaskComposer extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute()
    {
        if (!core\fs\File::iFileExists($this->app->path.'/composer.json')) {
            return;
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
