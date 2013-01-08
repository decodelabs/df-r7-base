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
    
class TaskCommit extends arch\Action {

    public function execute() {
        $response = new halo\task\Response([
            new core\io\channel\Std()
        ]);

        $name = $this->request->query['package'];
        core\stub($name);

        if(empty($name)) {
            return $this->arch->newRequest('packages/commit-all');
        }

        $response->writeLine('Pushing changes for package "'.$name.'"');
        $model = $this->data->getModel('package');

        if(!$result = $model->push($name)) {
            $response->writeLine('!! Package "'.$name.'" repo could not be found !!');
        } else {
            $response->write($result."\n");
        }
    }
}