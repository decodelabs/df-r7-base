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

class TaskPurgeErrorLogs extends arch\node\Task
{
    const MAX_LOOP = 250;

    public function execute()
    {
        $threshold = '-'.$this->data->pestControl->getPurgeThreshold();
        $loop = $total = 0;

        while (++$loop < self::MAX_LOOP) {
            $total += $count = $this->data->pestControl->errorLog->delete()
                ->where('isArchived', '=', false)
                ->beginWhereClause()
                    ->where('date', '<', $threshold)
                    ->orWhereCorrelation('error', 'in', 'id')
                        ->from('axis://pestControl/Error', 'error')
                        ->where('lastSeen', '<', $threshold)
                        ->endCorrelation()
                    ->endClause()
                ->limit(100)
                ->execute();

            if (!$count) {
                break;
            }

            usleep(50000);
        }


        $loop = 0;

        while (++$loop < self::MAX_LOOP) {
            $count = $this->data->pestControl->error->delete()
                ->where('lastSeen', '<', $threshold)
                ->whereCorrelation('id', '!in', 'error')
                    ->from('axis://pestControl/ErrorLog', 'log')
                    ->endCorrelation()
                ->limit(100)
                ->execute();

            if (!$count) {
                break;
            }

            usleep(50000);
        }

        $this->io->writeLine('Purged '.$total.' critical error logs');
    }
}
