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

class TaskRefresh extends arch\node\Task
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
            $this->runChild('git/refresh-all');
            return;
        }

        foreach ($names as $name) {
            Cli::{'brightMagenta'}('Pulling updates for '.$name.': ');
            $model = $this->data->getModel('package');

            if (!$result = $model->updateRemote($name, Cli::getSession())) {
                Cli::error('repo could not be found');
            } else {
                Cli::newLine();
                Cli::write($result);
                Cli::newLine();
            }
        }
    }
}
