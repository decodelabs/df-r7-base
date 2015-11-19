<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\pestControl\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPurgeAccessLogs extends arch\action\Task {

    public function execute() {
        $accesses = $this->data->pestControl->accessLog->delete()
            ->where('isArchived', '=', false)
            ->where('date', '<', '-'.$this->data->pestControl->getPurgeThreshold())
            ->execute();

        $this->io->writeLine('Purged '.$accesses.' access logs');
    }
}