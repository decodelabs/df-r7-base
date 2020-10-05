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

use DecodeLabs\Terminus as Cli;

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

            Cli::{'brightMagenta'}($package['name'].': ');
            $package['repo']->setCliSession(Cli::getSession());

            try {
                if (!$result = $package['repo']->pull()) {
                    Cli::error('repo could not be found');
                }
            } catch (spur\vcs\git\Exception $e) {
                Cli::writeError($e->getMessage());
                Cli::newErrorLine();
                return;
            }
        }

        $noBuild = isset($this->request['no-build']);

        if ($this->app->isDevelopment() && !$noBuild) {
            $this->runChild('app/build?dev', false);
        } elseif ($this->app->isTesting() && !$noBuild) {
            $this->runChild('app/build', false);
        }
    }
}
