<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\pestControl\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPurgeMissLogs extends arch\node\Task {

    public function execute() {
        $threshold = '-'.$this->data->pestControl->getPurgeThreshold();

        $misses = $this->data->pestControl->missLog->delete()
            ->where('isArchived', '=', false)
            ->beginWhereClause()
                ->where('date', '<', $threshold)
                ->orWhereCorrelation('miss', 'in', 'id')
                    ->from('axis://pestControl/Miss', 'miss')
                    ->where('lastSeen', '<', $threshold)
                    ->endCorrelation()
                ->endClause()
            ->execute();

        $this->data->pestControl->miss->delete()
            ->where('lastSeen', '<', $threshold)
            ->whereCorrelation('id', '!in', 'miss')
                ->from('axis://pestControl/MissLog', 'log')
                ->endCorrelation()
            ->execute();

        $this->io->writeLine('Purged '.$misses.' miss logs');
    }
}