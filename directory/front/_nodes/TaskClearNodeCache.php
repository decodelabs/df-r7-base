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

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

class TaskClearNodeCache extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public function execute(): void
    {
        Cli::{'yellow'}('Clearing node cache: ');
        $dir = Atlas::dir(Genesis::$hub->getLocalDataPath().'/node');

        if ($dir->exists()) {
            $dir->moveTo(Genesis::$hub->getLocalDataPath().'/node-old', 'node-'.time());
        }

        Atlas::deleteDir(Genesis::$hub->getLocalDataPath().'/node-old');
        Cli::success('done');
    }
}
