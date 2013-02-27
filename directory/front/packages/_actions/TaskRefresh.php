<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\packages\_actions;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;
    
class TaskRefresh extends arch\task\Action {

    protected function _run() {
        $name = $this->request->query['package'];

        if(empty($name)) {
            return $this->directory->newRequest('packages/refresh-all');
        }

        $this->response->writeLine('Refreshing package "'.$name.'"');
        $model = $this->data->getModel('package');

        if(!$result = $model->updateRemote($name)) {
            $this->response->writeLine('!! Package "'.$name.'" repo could not be found !!');
        } else {
            $this->response->write($result."\n");
        }
    }
}