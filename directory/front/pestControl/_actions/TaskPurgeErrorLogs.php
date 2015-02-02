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

class TaskPurgeErrorLogs extends arch\task\Action {
    
    public function execute() {
        $threshold = '-'.$this->data->pestControl->getPurgeThreshold();

        $errors = $this->data->pestControl->errorLog->delete()
            ->where('isArchived', '=', false)
            ->beginWhereClause()
                ->where('date', '<', $threshold)
                ->orWhereCorrelation('error', 'in', 'id')
                    ->from('axis://pestControl/Error', 'error')
                    ->where('lastSeen', '<', $threshold)
                    ->endCorrelation()
                ->endClause()
            ->execute();


        $this->data->pestControl->error->delete()
            ->where('lastSeen', '<', $threshold)
            ->whereCorrelation('id', '!in', 'error')
                ->from('axis://pestControl/ErrorLog', 'log')
                ->endCorrelation()
            ->execute();

        $this->io->writeLine('Purged '.$errors.' critical error logs');
    }
}