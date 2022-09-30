<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\git\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

class TaskInitGitignore extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        $path = Genesis::$hub->getApplicationPath();

        if (is_file($path.'/.gitignore')) {
            Cli::success('.gitignore file already in place');
            return;
        }

        Cli::{'yellow'}('Copying default .gitignore: ');

        Atlas::copyFile(__DIR__.'/default.gitignore', $path.'/.gitignore')
            ->setPermissions(0777);

        Cli::success('done');
    }
}
