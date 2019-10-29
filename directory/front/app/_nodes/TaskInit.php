<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\app\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;

use DecodeLabs\Terminus\Cli;

class TaskInit extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        $this->runChild('app/generate-entry');
        Cli::newLine();

        $this->runChild('config/init');
        Cli::newLine();

        $this->runChild('axis/set-master?check=false');
        Cli::newLine();

        if (!$this->data->user->client->countAll()) {
            $this->runChild('users/add?groups[]=developer');
            Cli::newLine();
        }

        $this->runChild('composer/init');
        Cli::newLine();

        $this->runChild('git/init');
        Cli::newLine();

        $this->runChild('theme/install-dependencies');
    }
}
