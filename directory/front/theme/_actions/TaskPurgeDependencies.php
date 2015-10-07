<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\spur;

class TaskPurgeDependencies extends arch\task\Action {

    public function execute() {
        $this->io->write('Purging theme dependencies...');

        /*
        $installer = new spur\packaging\bower\Installer($this->io);

        $packages = $installer->getInstalledPackages();

        if(empty($packages)) {
            $this->io->write(' !!none found!!');
        } else {
            foreach($packages as $path => $package) {
                $this->io->write(' '.$package->name);
                core\fs\Dir::delete($path);
            }
        }

        $this->io->writeLine();
        */

        $vendorPath = df\Launchpad::$application->getApplicationPath().'/assets/vendor/';
        core\fs\Dir::delete($vendorPath);

        $this->io->writeLine(' done');
    }
}