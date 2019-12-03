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

class TaskSpool extends arch\node\Task
{
    const SELF_REQUEST = 'tasks/spool';
    const COOLOFF = 20;
    const QUEUE_LIMIT = 10;

    protected $_log;
    protected $_timer;

    protected $_outputReceiver;
    protected $_errorReceiver;

    protected function _beforeDispatch()
    {
        $this->_outputReceiver = Atlas::$fs->newMemoryFile();
        $this->_errorReceiver = Atlas::$fs->newMemoryFile();

        Cli::getSession()->getBroker()
            ->addOutputReceiver($this->_outputReceiver)
            ->addErrorReceiver($this->_errorReceiver);

        $this->_log = $this->data->task->log->newRecord([
                'request' => self::SELF_REQUEST,
                'environmentMode' => df\Launchpad::$app->envMode,
                'status' => 'processing'
            ])
            ->save();
    }

    public function execute()
    {
        $this->_timer = new core\time\Timer();


        // Test to see if spool has run recently
        if (!$this->_checkLastRun()) {
            return;
        }

        // Clear out old logs
        $this->runChild('tasks/purge-logs?log='.$this->_log['id'], false);

        // Clear broken queue items
        $this->runChild('tasks/purge-queue', false);

        // Queue scheduled tasks
        $this->runChild('tasks/queue-scheduled', false);


        // Select and lock queued tasks
        Cli::{'yellow'}('Selecting queued tasks: ');

        $count = $this->data->task->queue->update([
                'lockDate' => 'now',
                'lockId' => $this->_log['id']
            ])
            ->where('lockDate', '=', null)
            ->where('lockId', '=', null)
            ->orderBy('queueDate ASC')
            ->limit(self::QUEUE_LIMIT)
            ->execute();

        if (!$count) {
            Cli::success('no tasks to launch right now!');
            return;
        }

        Cli::success('locked '.$count.' entries');


        // Launch tasks
        $taskIds = $this->data->task->queue->select('id', 'request')
            ->where('lockId', '=', $this->_log['id'])
            ->orderBy('priority DESC', 'queueDate ASC')
            ->toList('id', 'request');

        foreach ($taskIds as $taskId => $request) {
            Cli::newLine();
            Cli::comment($request.' : '.$taskId);

            $this->runChild('tasks/launch-queued?id='.$taskId, false);
        }

        $this->data->task->queue->delete()
            ->where('id', 'in', array_keys($taskIds))
            ->execute();
    }

    protected function _checkLastRun(): bool
    {
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

        if ($justRun) {
            Cli::info('The spool task has run recently - please try again later');
            $this->_log->delete();
            return false;
        }

        return true;
    }

    protected function _afterDispatch($output)
    {
        $this->_finalizeLog();
        return $output;
    }

    public function handleException(\Throwable $e)
    {
        Cli::writeErrorLine((string)$e);
        $this->_finalizeLog();

        $this->logs->logException($e);

        /*
        try {
            $this->comms->adminNotify(
                'Task manager failure',
                'The task manager spool process failed with the following exception: '."\n\n".$exception
            );
        } catch(\Throwable $e) {
            // Never mind :)
        }
        */

        parent::handleException($e);
    }

    protected function _finalizeLog()
    {
        if (!$this->_log || $this->_log->isNew()) {
            return;
        }

        $output = $this->_outputReceiver->getContents();
        $error = $this->_errorReceiver->getContents();

        if (!strlen($output)) {
            $output = null;
        }

        if (!strlen($error)) {
            $error = null;
        }

        try {
            $this->_log->output = $output;
            $this->_log->errorOutput = $error;
            $this->_log->runTime = $this->_timer->getTime();
            $this->_log->status = 'complete';
            $this->_log->save();
        } catch (\Exception $e) {
            $this->_log->delete();
        }
    }
}
