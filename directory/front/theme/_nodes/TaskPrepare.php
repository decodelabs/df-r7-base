<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPrepare extends arch\node\Task {

    public function execute() {
        $this->runChild('theme/install-dependencies', false);
        $this->runChild('theme/rebuild-sass', false);

        $this->io->writeLine(' done');
    }
}