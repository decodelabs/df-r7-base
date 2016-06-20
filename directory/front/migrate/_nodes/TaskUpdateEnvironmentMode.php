<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\migrate\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskUpdateEnvironmentMode extends arch\node\Task {

    public function execute() {
        $this->runChild('application/build');
        $this->runChild('axis/rebuild-table?unit=mail/capture&delete');
        $this->runChild('axis/rebuild-table?unit=mail/journal&delete');
        $this->runChild('axis/rebuild-table?unit=task/log&delete');
    }
}