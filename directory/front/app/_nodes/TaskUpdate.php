<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\app\_nodes;

use DecodeLabs\Terminus as Cli;
use df\arch;

use df\spur;

class TaskUpdate extends arch\node\Task
{
    public function execute(): void
    {
        // Ensure source
        $this->ensureDfSource();

        // Pull app repo
        $this->gitUpdate();

        // Install composer
        $this->runChild('composer/install');
        Cli::newLine();

        // Build app
        $this->launch('app/build');
    }

    protected function gitUpdate(): void
    {
        $model = $this->data->getModel('package');

        try {
            if (!$result = $model->pull('app', Cli::getSession())) {
                Cli::error('repo could not be found');
            }

            Cli::newLine();
        } catch (spur\vcs\git\Exception $e) {
            Cli::newErrorLine();
            return;
        }
    }
}
