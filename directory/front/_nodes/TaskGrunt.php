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

class TaskGrunt extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute()
    {
        if (!core\fs\File::iFileExists($this->app->path.'/gruntfile.js')) {
            $this->io->writeLine('No gruntfile.js found');
            return;
        }

        $this->io->writeLine('Calling grunt');

        halo\process\launcher\Base::factory('grunt')
            ->setWorkingDirectory($this->app->path)
            ->setMultiplexer($this->io)
            ->launch();
    }
}
