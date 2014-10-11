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
    
class TaskRefreshAll extends arch\task\Action {

    public function execute() {
        $this->io->writeLine('Finding all package git repositories...');
        $model = $this->data->getModel('package');

        foreach($model->getInstalledPackageList() as $package) {
            if(!$package['repo']) {
                continue;
            }

            $this->io->writeLine('Refreshing package "'.$package['name'].'"');
            $result = $package['repo']->updateRemote();

            $this->io->write($result."\r\n");
        }

        $this->io->writeLine('Done');
    }
}