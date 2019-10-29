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

use DecodeLabs\Terminus\Cli;
use DecodeLabs\Atlas;

class TaskInitGitignore extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        $path = df\Launchpad::$app->path;

        if (is_file($path.'/.gitignore')) {
            Cli::success('.gitignore file already in place');
            return;
        }

        Cli::{'yellow'}('Copying default .gitignore: ');

        Atlas::$fs->copyFile(__DIR__.'/default.gitignore', $path.'/.gitignore')
            ->setPermissions(0777);

        Cli::success('done');
    }
}
