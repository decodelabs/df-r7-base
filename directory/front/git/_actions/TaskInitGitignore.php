<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\git\_actions;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;
    
class TaskInitGitignore extends arch\task\Action {

    public function execute() {
        $this->io->writeLine('Copying default .gitignore file');
        $path = df\Launchpad::$applicationPath;

        if(!is_file($path.'/.gitignore')) {
            core\io\Util::copyFile(__DIR__.'/default.gitignore', $path.'/.gitignore');
            core\io\Util::chmod($path.'/.gitignore', 0777);

            if(!is_file($path.'/.gitignore')) {
                $this->io->writeErrorLine('Unable to copy .gitignore file');
            }
        }
    }
}