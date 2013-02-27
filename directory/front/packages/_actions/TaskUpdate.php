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
    
class TaskUpdate extends arch\task\Action {

    protected function _run() {
        $name = $this->request->query['package'];

        if(empty($name)) {
            return $this->arch->newRequest('packages/update-all');
        }

        $this->response->writeLine('Pulling updates for package "'.$name.'"');
        $model = $this->data->getModel('package');

        if(!$result = $model->pull($name)) {
            $this->response->writeLine('!! Package "'.$name.'" repo could not be found !!');
        } else {
            $this->response->write($result."\n");
        }
    }
}