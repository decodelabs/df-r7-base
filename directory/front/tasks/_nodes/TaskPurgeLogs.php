<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\tasks\_nodes;

use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskPurgeLogs extends arch\node\Task
{
    public function execute(): void
    {
        // Clear out old logs
        Cli::{'yellow'}('Clearing old logs: ');
        $logId = $this->request['log'];

        $count = $this->data->task->log->delete()
            ->beginWhereClause()
                ->beginWhereClause()
                    ->where('startDate', '<', '-1 week')
                    ->where('errorOutput', '=', null)
                    ->endClause()
                ->beginOrWhereClause()
                    ->where('startDate', '<', '-4 weeks')
                    ->where('errorOutput', '!=', null)
                    ->endClause()
                ->endClause()

            ->chainIf($logId !== null, function ($query) use ($logId) {
                $query->beginOrWhereClause()
                    ->where('request', '=', 'tasks/spool')
                    ->where('id', '!=', $logId)
                    ->where('errorOutput', '=', null)
                    ->endClause();
            })

            ->execute();

        try {
            $this->data->task->log->update(['status' => 'lagging'])
                ->where('startDate', '<', '-30 minutes')
                ->where('status', '=', 'processing')
                ->execute();
        } catch (\Exception $e) {
        }

        Cli::success($count . ' logs');
    }
}
