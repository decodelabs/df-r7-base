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

use DecodeLabs\Systemic;

class TaskBower extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute()
    {
        if (!core\fs\File::iFileExists($this->app->path.'/bower.json')) {
            $this->io->writeLine('No bower.json found');
            return;
        }

        $this->io->writeLine('Calling bower');

        Systemic::$process->newLauncher('bower install')
            ->setWorkingDirectory($this->app->path)
            ->setR7Multiplexer($this->io)
            ->launch();
    }
}
