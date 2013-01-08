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
    
class TaskUpdateAll extends arch\Action {

    public function execute() {
        $response = new halo\task\Response([
            new core\io\channel\Std()
        ]);

        $response->writeLine('Finding all package repositories...');
        $model = $this->data->getModel('package');

        foreach($model->getInstalledPackageList() as $package) {
            if(!$package['repo']) {
                continue;
            }

            $response->writeLine('Pulling updates for package "'.$package['name'].'"');
            $result = $package['repo']->pull();

            $response->write($result."\r\n");
        }

        $response->writeLine('Done');
    }
}