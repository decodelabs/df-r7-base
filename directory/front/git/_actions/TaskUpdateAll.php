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
    
class TaskUpdateAll extends arch\task\Action {

    public function execute() {
        $this->io->writeLine('Finding all package repositories...');
        $model = $this->data->getModel('package');

        foreach($model->getInstalledPackageList() as $package) {
            if(!$package['repo']) {
                continue;
            }

            $this->io->writeLine('Pulling updates for package "'.$package['name'].'"');
            $result = $package['repo']->pull();

            $this->io->writeLine($result);
        }

        $this->io->writeLine('Done');

        if(is_dir($this->application->getLocalStoragePath().'/run')) {
            $this->runChild('application/build?testing=1', false);
        }
    }
}