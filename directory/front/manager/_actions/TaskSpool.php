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

class TaskSpool extends arch\task\Action {
    
    const SELF_REQUEST = 'manager/spool';
    const COOLOFF = 20;
    const QUEUE_LIMIT = 10;

    protected $_log;
    protected $_channel;
    protected $_timer;

    protected function _beforeDispatch() {
        $this->_channel = (new core\io\channel\Memory('', 'text/plain'))->setId('buffer');
        $this->io->addChannel($this->_channel);

        $this->_log = $this->data->task->log->newRecord([
                'request' => self::SELF_REQUEST,
                'environmentMode' => df\Launchpad::getEnvironmentMode()
            ])
            ->save();
    }

    public function execute() {
        $this->_timer = new core\time\Timer();

        // Test to see if spool has run recently
        $justRun = $this->data->task->log->select('id')
            ->where('request', '=', self::SELF_REQUEST)
            ->where('id', '!=', $this->_log['id'])
            ->beginWhereClause()
                ->where('startDate', '>', '-'.self::COOLOFF.' seconds')
                ->beginOrWhereClause()
                    ->where('startDate', '>', '-30 minutes')
                    ->where('runTime', '=', null)
                ->endClause()
            ->endClause()
            ->count();

        if($justRun) {
            $this->io->writeErrorLine('The spool task has already run within the previous cooloff period - please wait a little while before trying again');
            $this->_log->delete();
            return;
        }


        // Clear out old logs
        $this->io->write('Clearing old logs...');

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
            ->beginOrWhereClause()
                ->where('request', '=', self::SELF_REQUEST)
                ->where('id', '!=', $this->_log['id'])
                ->where('errorOutput', '=', null)
                ->endClause()
            ->execute();

        $this->io->writeLine(' deleted '.$count.' entries');


        // Clear broken queue items
        $this->data->task->queue->delete()
            ->where('lockDate', '<', '-1 week')
            ->execute();


        // Queue scheduled tasks
        $this->runChild('manager/queue-scheduled');


        // Select and lock queued tasks
        $this->io->write('Selecting queued tasks...');

        $count = $this->data->task->queue->update([
                'lockDate' => 'now',
                'lockId' => $this->_log['id']
            ])
            ->where('lockDate', '=', null)
            ->where('lockId', '=', null)
            ->orderBy('queueDate ASC')
            ->limit(self::QUEUE_LIMIT)
            ->execute();

        if(!$count) {
            $this->io->writeLine(' no tasks to launch right now!');
            return;
        }

        $this->io->writeLine(' locked '.$count.' entries');


        // Launch tasks
        $taskIds = $this->data->task->queue->select('id', 'request')
            ->where('lockId', '=', $this->_log['id'])
            ->orderBy('priority DESC', 'queueDate ASC')
            ->toList('id', 'request');

        foreach($taskIds as $taskId => $request) {
            $this->io->writeLine();
            $this->io->writeLine('Launching task '.$request.' id: '.$taskId);

            $this->io->removeChannel($this->_channel);
            $this->runChild('manager/launch-queued?id='.$taskId);
            $this->io->addChannel($this->_channel);
        }

        $this->data->task->queue->delete()
            ->where('id', 'in', array_keys($taskIds))
            ->execute();
    }

    protected function _afterDispatch($output) {
        $this->_finalizeLog();
    }

    public function handleException(\Exception $e) {
        $context = new core\debug\Context();
        $context->exception($e);
        $exception = (new core\debug\renderer\PlainText($context))->render();
        $this->_channel->writeError($exception);
        $this->_finalizeLog();

        try {
            $this->comms->adminNotify(
                'Task manager failure',
                'The task manager spool process failed with the following exception: '."\n\n".$exception
            );
        } catch(\Exception $e) {
            // Never mind :)
        }

        parent::handleException($e);
    }

    protected function _finalizeLog() {
        if(!$this->_log || $this->_log->isNew()) {
            return;
        }

        $output = $this->_channel->getContents();
        $error = $this->_channel->getErrorBuffer();

        if(!strlen($output)) {
            $output = null;
        }

        if(!strlen($error)) {
            $error = null;
        }

        $this->_log->output = $output;
        $this->_log->errorOutput = $error;
        $this->_log->runTime = $this->_timer->getTime();
        $this->_log->save();
    }
}