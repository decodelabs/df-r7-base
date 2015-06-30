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
    
class TaskRefresh extends arch\task\Action {

    public function extractCliArguments(core\cli\ICommand $command) {
        foreach($command->getArguments() as $arg) {
            if(!$arg->isOption()) {
                $this->request->query->packages[] = (string)$arg;
            }
        }
    }
    
    public function execute() {
        $names = $this->request->query->packages->toArray();

        if($this->request->query->has('package')) {
            $names[] = $this->request->query['package'];
        }

        if(empty($names)) {
            $this->runChild('git/refresh-all', false);
            return;
        }

        foreach($names as $name) {
            $this->io->writeLine('Pulling updates for package "'.$name.'"');
            $model = $this->data->getModel('package');

            if(!$result = $model->updateRemote($name)) {
                $this->io->writeLine('!! Package "'.$name.'" repo could not be found !!');
            } else {
                $this->io->write($result."\n");
            }
        }
    }
}