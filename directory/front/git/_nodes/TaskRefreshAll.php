<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\git\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;

class TaskRefreshAll extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        $this->io->writeLine('Finding all package git repositories...');
        $model = $this->data->getModel('package');

        foreach ($model->getInstalledPackageList() as $package) {
            if (!$package['repo']) {
                continue;
            }

            $this->io->writeLine('Refreshing package "'.$package['name'].'"');
            $package['repo']->setMultiplexer($this->io);
            $package['repo']->updateRemote();
        }

        $this->io->writeLine('Done');
    }
}
