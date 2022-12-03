<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\pestControl\_nodes;

use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskPurgeErrorLogs extends arch\node\Task
{
    public const MAX_LOOP = 250;

    public function execute(): void
    {
        $all = isset($this->request['all']);
        $threshold = '-' . $this->data->pestControl->getPurgeThreshold();
        $loop = $total = 0;

        while (++$loop < self::MAX_LOOP) {
            $total += $count = $this->data->pestControl->errorLog->delete()
                ->where('isArchived', '=', false)
                ->chainIf(!$all, function ($query) use ($threshold) {
                    $query->beginWhereClause()
                        ->where('date', '<', $threshold)
                        ->orWhereCorrelation('error', 'in', 'id')
                            ->from('axis://pestControl/Error', 'error')
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

        Cli::success('Purged ' . $total . ' critical error records');


        $loop = $total = 0;

        while (++$loop < self::MAX_LOOP) {
            $total += $count = $this->data->pestControl->error->delete()
                ->chainIf(!$all, function ($query) use ($threshold) {
                    $query->where('lastSeen', '<', $threshold);
                })
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

        Cli::success('Purged ' . $total . ' critical error logs');


        $loop = $total = 0;

        while (++$loop < self::MAX_LOOP) {
            $total += $count = $this->data->pestControl->stackTrace->delete()
                ->whereCorrelation('id', '!in', 'stackTrace')
                    ->from('axis://pestControl/ErrorLog', 'log')
                    ->endCorrelation()
                ->limit(100)
                ->execute();

            if (!$count) {
                break;
            }

            usleep(50000);
        }

        Cli::success('Purged ' . $total . ' stack traces');
    }
}
