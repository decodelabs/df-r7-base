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
    
class TaskInit extends arch\task\Action {

    public function execute() {
        $this->io->writeLine('Initialising app...');
        $this->runChild('application/generate-base-entry');
        $this->runChild('config/init');
        $this->runChild('git/init');
        $this->runChild('theme/intall-dependencies');
    }
}