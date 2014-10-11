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
    
    protected $_count = 0;

    public function execute() {
        $this->io->write('Queuing scheduled tasks...');

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

        foreach($scheduleList as $id => $task) {
            $schedule = core\time\Schedule::factory($task->toArray());
            $lastTrigger = $schedule->getLast(null, 1);

            if(!$task['lastRun']) {
                $this->_trigger($task);
                continue;
            }

            if($task['lastRun']->lt($lastTrigger)) {
                $this->_trigger($task);
                continue;
            }
        }

        $this->io->writeLine(' '.$this->_count.' entries prepared for launch');
    }

    protected function _trigger($task) {
        $this->_count++;

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
}