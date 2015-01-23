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

class TaskPurgeMissLogs extends arch\task\Action {
    
    const THRESHOLD = '-2 months';

    public function execute() {
        $misses = $this->data->pestControl->missLog->delete()
            ->where('isArchived', '=', false)
            ->beginWhereClause()
                ->where('date', '<', self::THRESHOLD)
                ->orWhereCorrelation('miss', 'in', 'id')
                    ->from('axis://pestControl/Miss', 'miss')
                    ->where('lastSeen', '<', self::THRESHOLD)
                    ->endCorrelation()
                ->endClause()
            ->execute();

        $this->data->pestControl->miss->delete()
            ->where('lastSeen', '<', self::THRESHOLD)
            ->whereCorrelation('id', '!in', 'miss')
                ->from('axis://pestControl/MissLog', 'log')
                ->endCorrelation()
            ->execute();

        $this->io->writeLine('Purged '.$misses.' miss logs');
    }
}