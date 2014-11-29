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
use df\halo;
    
class TaskGrunt extends arch\task\Action {

    public function execute() {
        if(!is_file($this->application->getApplicationPath().'/gruntfile.js')) {
            return;
        }

        $this->io->writeLine('Calling grunt');
        
        halo\process\launcher\Base::factory('grunt')
            ->setWorkingDirectory($this->application->getApplicationPath())
            ->setMultiplexer($this->io)
            ->launch();
    }
}