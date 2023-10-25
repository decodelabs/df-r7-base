<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\app\_nodes;

use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskInit extends arch\node\Task
{
    public function execute(): void
    {
        $this->ensureDfSource();

        $this->runChild('axis/set-master?check=false');
        Cli::newLine();

        if (!$this->data->user->client->countAll()) {
            $this->runChild('users/add?groups[]=developer');
            Cli::newLine();
        }

        $this->runChild('composer/init');
        Cli::newLine();

        $this->runChild('theme/install-dependencies');
    }
}
