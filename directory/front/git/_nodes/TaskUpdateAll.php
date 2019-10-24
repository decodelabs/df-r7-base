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
use df\spur;

class TaskUpdateAll extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        $model = $this->data->getModel('package');

        foreach ($model->getInstalledPackageList() as $package) {
            if (!$package['repo']) {
                continue;
            }

            $this->io->writeLine('# git pull "'.$package['name'].'"');
            $package['repo']->setMultiplexer($this->io);

            try {
                if (!$result = $package['repo']->pull()) {
                    $this->io->writeLine('!! Package "'.$package['name'].'" repo could not be found !!');
                }

                $this->io->writeLine();
            } catch (spur\vcs\git\EGlitch $e) {
                $this->io->writeErrorLine($e->getMessage());
                return;
            }
        }

        $this->io->writeLine('Done');
        $noBuild = isset($this->request['no-build']);

        if ($this->app->isDevelopment() && !$noBuild) {
            $this->runChild('app/build?dev');
        } elseif ($this->app->isTesting() && !$noBuild) {
            $this->runChild('app/build');
        }
    }
}
