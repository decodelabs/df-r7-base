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

class TaskBower extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute()
    {
        if (!core\fs\File::iFileExists($this->app->path.'/bower.json')) {
            $this->io->writeLine('No bower.json found');
            return;
        }

        $this->io->writeLine('Calling bower');

        halo\process\launcher\Base::factory('bower install')
            ->setWorkingDirectory($this->app->path)
            ->setMultiplexer($this->io)
            ->launch();
    }
}
