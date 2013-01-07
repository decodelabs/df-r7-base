<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\_actions;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;
    
class TaskInitGitignore extends arch\Action {

    public function execute() {
        $response = new halo\task\Response([
            new core\io\channel\Std()
        ]);

        $response->writeLine('Copying default .gitignore file...');

        $path = df\Launchpad::$applicationPath;

        core\io\Util::copyFile(__DIR__.'/default.gitignore', $path.'/.gitignore');
        core\io\Util::chmod($path.'/.gitignore', 0777);

        if(is_file($path.'/.gitignore')) {
            $response->writeLine('Done');
        } else {
            $response->writeError('Unable to copy .gitignore file');
        }
    }
}