<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\manager\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskQueueScheduled extends arch\task\Action {
    
    public function execute() {
        $this->response->write('Queuing scheduled tasks...');
        $count = 0;
        
        $tasks = $this->data->task->schedule->fetch()
            ->beginWhereClause()
                ->where('lastRun', '<', '-1 minute')
                ->orWhere('lastRun', '=', null)
                ->endClause()
            ->whereCorrelation('request', '!in', 'request')
                ->from('axis://task/Queue', 'queue')
                ->endCorrelation()
            ->where('isLive', '=', true)
            ->orderBy('lastRun ASC')
            ->toArray();

        foreach($tasks as $task) {
            if(!$task->canQueue()) {
                continue;
            }

            $count++;

            $task->lastRun = 'now';
            $task->save();

            $queue = $this->data->newRecord(
                    'axis://task/Queue', 
                    [
                        'request' => $task['request'],
                        'environmentMode' => $task['environmentMode'],
                        'priority' => $task['priority']
                    ]
                )
                ->save();
        }

        $this->response->writeLine(' '.$count.' entries prepared for launch');
    }
}