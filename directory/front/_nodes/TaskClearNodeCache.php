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

use DecodeLabs\Terminus as Cli;
use DecodeLabs\Atlas;

class TaskClearNodeCache extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute()
    {
        Cli::{'yellow'}('Clearing node cache: ');
        $dir = Atlas::dir($this->app->getLocalDataPath().'/node');

        if ($dir->exists()) {
            $dir->moveTo($this->app->getLocalDataPath().'/node-old', 'node-'.time());
        }

        Atlas::deleteDir($this->app->getLocalDataPath().'/node-old');
        Cli::success('done');
    }
}
