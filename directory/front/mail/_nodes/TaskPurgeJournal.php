<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\mail\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPurgeJournal extends arch\node\Task {

    const SCHEDULE = '0 3 * * *';
    const SCHEDULE_AUTOMATIC = true;

    public function execute() {
        $deleted = $this->data->mail->journal->delete()
            ->where('expireDate', '!=', null)
            ->where('expireDate', '<', 'now')
            ->execute();

        $this->io->writeLine('Purged '.$deleted.' mail journal records');
    }
}