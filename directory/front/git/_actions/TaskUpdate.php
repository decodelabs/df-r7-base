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
    
class TaskUpdate extends arch\task\Action {

    protected function _run() {
        $names = $this->request->query->packages->toArray();

        if($this->request->query->has('package')) {
            $names[] = $this->request->query['package'];
        }

        if(empty($names)) {
            return $this->directory->newRequest('git/update-all');
        }

        foreach($names as $name) {
            $this->response->writeLine('Pulling updates for package "'.$name.'"');
            $model = $this->data->getModel('package');

            if(!$result = $model->pull($name)) {
                $this->response->writeLine('!! Package "'.$name.'" repo could not be found !!');
            } else {
                $this->response->write($result."\n");
            }
        }

        if(is_dir($this->application->getLocalDataStoragePath().'/run')) {
            return $this->directory->newRequest('application/build?testing');
        }
    }
}