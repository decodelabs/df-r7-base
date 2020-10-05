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

class TaskUpdate extends arch\node\Task
{
    public function extractCliArguments(core\cli\ICommand $command)
    {
        foreach ($command->getArguments() as $arg) {
            if (!$arg->isOption()) {
                $this->request->query->packages[] = (string)$arg;
            }
        }
    }

    public function execute()
    {
        $this->ensureDfSource();
        $names = $this->request->query->packages->toArray();

        if ($this->request->query->has('package')) {
            $names[] = $this->request['package'];
        }

        if (empty($names)) {
            $this->runChild('git/update-all', false);
            return;
        }

        foreach ($names as $name) {
            Cli::{'brightMagenta'}($name.': ');
            $model = $this->data->getModel('package');

            try {
                if (!$result = $model->pull($name, Cli::getSession())) {
                    Cli::error('repo could not be found');
                }

                Cli::newLine();
            } catch (spur\vcs\git\Exception $e) {
                Cli::newErrorLine();
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
