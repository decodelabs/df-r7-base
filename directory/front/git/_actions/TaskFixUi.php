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
    
class TaskFixUi extends arch\task\Action {

    const GEOMETRY = '1914x1036+5+23 450 300';

    public function execute() {
        $this->io->write('Updating repositories...');
        $model = $this->data->getModel('package');

        foreach($model->getInstalledPackageList() as $package) {
            if(!$repo = $package['repo']) {
                continue;
            }

            if($repo->getConfig('gui.geometry') != self::GEOMETRY) {
                $this->io->write(' '.$package['name']);

                $repo->setConfig('gui.wmstate', 'zoomed');
                $repo->setConfig('gui.geometry', self::GEOMETRY);
            }
        }

        $this->io->writeLine();
    }
}