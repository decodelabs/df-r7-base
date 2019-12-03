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

class TaskQueueScheduled extends arch\node\Task
{
    protected $_count = 0;

    public function execute()
    {
        Cli::{'yellow'}('Queuing scheduled tasks');

        if (!$this->data->task->schedule->countAll()) {
            Cli::newLine();
            $this->runChild('./scan');
        } else {
            Cli::{'yellow'}(': ');
        }

        $scheduleList = $this->data->task->schedule->fetch()
            ->beginWhereClause()
                ->where('lastRun', '<', '-50 seconds')
                ->orWhere('lastRun', '=', null)
                ->endClause()
            ->whereCorrelation('request', '!in', 'request')
                ->from('axis://task/Queue', 'queue')
                ->endCorrelation()
            ->where('isLive', '=', true)
            ->orderBy('lastRun ASC');

        $queue = [];
        $now = time();

        foreach ($scheduleList as $id => $task) {
            $schedule = core\time\Schedule::factory($task->toArray());
            $lastTrigger = $schedule->getLast(null, 1);

            if (!$task['lastRun']) {
                $this->_trigger($task);
                continue;
            }

            if ($task['lastRun']->lt($lastTrigger)) {
                $this->_trigger($task);
                continue;
            }
        }

        Cli::success($this->_count.' tasks queued');
    }

    protected function _trigger($task)
    {
        $this->_count++;

        $task->lastRun = 'now';
        $task->save();

        $queue = $this->data->newRecord('axis://task/Queue', [
                'request' => $task['request'],
                'priority' => $task['priority'],
                'status' => 'pending'
            ])
            ->save();
    }
}
