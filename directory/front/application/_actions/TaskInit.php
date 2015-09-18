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

        $this->io->writeLine();
        $this->runChild('config/init');

        $this->io->writeLine();
        $this->runChild('git/init');
        
        $this->io->writeLine();
        $this->io->incrementLineLevel();
        $this->io->writeLine('Set master database connection...');
        $this->io->decrementLineLevel();
        $this->runChild('axis/set-master?check=false');

        if(!$this->data->user->client->countAll()) {
            $this->io->writeLine();
            $this->io->writeLine('Add root user');
            $this->runChild('users/add?groups[]=developer');
        }

        $this->io->writeLine();
        $this->runChild('theme/install-dependencies');
    }
}