<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\build\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskClearNodeCache extends arch\task\Action {
    
    public function execute() {
        $this->io->write('Clearing node cache...');

        $path = $this->application->getLocalStoragePath().'/node';
        $time = time();

        core\io\Util::renameDir($path, 'node-'.$time);
        core\io\Util::deleteDir($path.'/node-'.$time);
        
        $this->io->writeLine(' done');
    }
}