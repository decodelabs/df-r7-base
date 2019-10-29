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

use DecodeLabs\Terminus\Cli;

class TaskRefreshAll extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();
        $model = $this->data->getModel('package');

        foreach ($model->getInstalledPackageList() as $package) {
            if (!$package['repo']) {
                continue;
            }

            Cli::{'yellow'}($package['name'].' ');
            $package['repo']->setMultiplexer($this->io);
            $package['repo']->updateRemote();
            Cli::success('done');
        }
    }
}
