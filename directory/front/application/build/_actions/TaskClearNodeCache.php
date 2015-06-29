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
        $dir = new core\fs\Dir($this->application->getLocalStoragePath().'/node');

        if($dir->exists()) {
            $dir->renameTo('node-'.time())->unlink();
        }
        
        $this->io->writeLine(' done');
    }
}