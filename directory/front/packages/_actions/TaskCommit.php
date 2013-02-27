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
    
class TaskCommit extends arch\task\Action {

    protected function _run() {
        $response = $this->task->getResponse();

        $name = $this->request->query['package'];
        core\stub($name);

        if(empty($name)) {
            return $this->arch->newRequest('packages/commit-all');
        }

        $this->response->writeLine('Pushing changes for package "'.$name.'"');
        $model = $this->data->getModel('package');

        if(!$result = $model->push($name)) {
            $this->response->writeLine('!! Package "'.$name.'" repo could not be found !!');
        } else {
            $this->response->write($result."\n");
        }
    }
}