<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\tasks\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

use DecodeLabs\Terminus\Cli;

class TaskPurgeLogs extends arch\node\Task
{
    public function execute()
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

        Cli::success($count.' logs');
    }
}
