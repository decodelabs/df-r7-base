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

    protected function _run() {
        $this->response->writeLine('Copying default .gitignore file...');

        $path = df\Launchpad::$applicationPath;

        core\io\Util::copyFile(__DIR__.'/default.gitignore', $path.'/.gitignore');
        core\io\Util::chmod($path.'/.gitignore', 0777);

        if(is_file($path.'/.gitignore')) {
            $this->response->writeLine('Done');
        } else {
            $this->response->writeError('Unable to copy .gitignore file');
        }
    }
}