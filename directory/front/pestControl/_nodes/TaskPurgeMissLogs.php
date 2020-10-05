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

use DecodeLabs\Terminus as Cli;

class TaskPurgeMissLogs extends arch\node\Task
{
    const MAX_LOOP = 250;

    public function execute()
    {
        $all = isset($this->request['all']);
        $threshold = '-'.$this->data->pestControl->getPurgeThreshold();
        $loop = $total = 0;

        while (++$loop < self::MAX_LOOP) {
            $total += $count = $this->data->pestControl->missLog->delete()
                ->where('isArchived', '=', false)
                ->chainIf(!$all, function ($query) use ($threshold) {
                    $query->beginWhereClause()
                        ->where('date', '<', $threshold)
                        ->orWhereCorrelation('miss', 'in', 'id')
                            ->from('axis://pestControl/Miss', 'miss')
                            ->where('lastSeen', '<', $threshold)
                            ->endCorrelation()
                        ->endClause();
                })
                ->limit(100)
                ->execute();

            if (!$count) {
                break;
            }

            usleep(50000);
        }

        $loop = 0;

        while (++$loop < self::MAX_LOOP) {
            $count = $this->data->pestControl->miss->delete()
                ->chainIf(!$all, function ($query) use ($threshold) {
                    $query->where('lastSeen', '<', $threshold);
                })
                ->whereCorrelation('id', '!in', 'miss')
                    ->from('axis://pestControl/MissLog', 'log')
                    ->endCorrelation()
                ->limit(100)
                ->execute();

            if (!$count) {
                break;
            }

            usleep(50000);
        }

        Cli::success('Purged '.$total.' miss logs');
    }
}
