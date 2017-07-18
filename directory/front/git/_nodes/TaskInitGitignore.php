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

class TaskInitGitignore extends arch\node\Task {

    public function execute() {
        $this->ensureDfSource();

        $path = df\Launchpad::$app->path;

        if(!is_file($path.'/.gitignore')) {
            $this->io->writeLine('Copying default .gitignore file');

            core\fs\File::copy(__DIR__.'/default.gitignore', $path.'/.gitignore')
                ->setPermissions(0777);
        }
    }
}
