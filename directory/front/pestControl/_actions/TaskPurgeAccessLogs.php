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

class TaskPurgeAccessLogs extends arch\task\Action {
    
    const THRESHOLD = '-2 months';

    public function execute() {
        $accesses = $this->data->pestControl->accessLog->delete()
            ->where('isArchived', '=', false)
            ->where('date', '<', self::THRESHOLD)
            ->execute();

        $this->io->writeLine('Purged '.$accesses.' access logs');
    }
}