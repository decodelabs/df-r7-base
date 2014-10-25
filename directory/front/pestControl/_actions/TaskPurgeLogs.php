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

class TaskPurgeLogs extends arch\task\Action {
    
    const SCHEDULE = '0 16 * * *';
    const SCHEDULE_AUTOMATIC = true;

    public function execute() {
        $errors = $this->data->pestControl->errorLog->delete()
            ->where('isArchived', '=', false)
            ->where('date', '<', '-3 months')
            ->execute();

        $this->io->writeLine('Purged '.$errors.' critical error logs');

        $misses = $this->data->pestControl->missLog->delete()
            ->where('isArchived', '=', false)
            ->beginWhereClause()
                ->where('date', '<', '-3 months')
                ->orWhereCorrelation('miss', 'in', 'id')
                    ->from('axis://pestControl/Miss', 'miss')
                    ->where('date', '<', '-3 months')
                    ->endCorrelation()
                ->endClause()
            ->execute();

        $this->data->pestControl->miss->delete()
            ->where('date', '<', '-3 months')
            ->execute();

        $this->io->writeLine('Purged '.$misses.' miss logs');

        $accesses = $this->data->pestControl->accessLog->delete()
            ->where('isArchived', '=', false)
            ->where('date', '<', '-3 months')
            ->execute();

        $this->io->writeLine('Purged '.$accesses.' access logs');
    }
}