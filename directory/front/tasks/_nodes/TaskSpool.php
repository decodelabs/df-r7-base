<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\tasks\_nodes;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\core;

class TaskSpool extends arch\node\Task
{
    public const SELF_REQUEST = 'tasks/spool';
    public const COOLOFF = 20;
    public const QUEUE_LIMIT = 10;

    protected $_log;
    protected $_timer;

    protected $_outputReceiver;
    protected $_errorReceiver;

    protected function _beforeDispatch(): void
    {
        $this->_outputReceiver = Atlas::newMemoryFile();
        $this->_errorReceiver = Atlas::newMemoryFile();

        Cli::getSession()->getBroker()
            ->addOutputReceiver($this->_outputReceiver)
            ->addErrorReceiver($this->_errorReceiver);

        $this->_log = $this->data->task->log->newRecord([
                'request' => self::SELF_REQUEST,
                'environmentMode' => Genesis::$environment->getMode(),
                'status' => 'processing'
            ])
            ->save();
    }

    public function execute(): void
    {
        $this->_timer = new core\time\Timer();


        // Test to see if spool has run recently
        if (!$this->_checkLastRun()) {
            return;
        }

        // Clear out old logs
        $this->runChild('tasks/purge-logs?log=' . $this->_log['id'], false);

        // Clear broken queue items
        $this->runChild('tasks/purge-queue', false);

        // Queue scheduled tasks
        $this->runChild('tasks/queue-scheduled', false);

        Cli::newLine();

        // Loop tasks
        $i = self::QUEUE_LIMIT;

        while ($i > 0) {
            $i--;

            if (!$task = $this->_lockNextTask()) {
                return;
            }

            Cli::{'brightMagenta'}($task['request'] . ' ');

            Legacy::launchTask('tasks/launch-queued?id=' . $task['id']);

            if (!$this->_checkCompleted($task['id'])) {
                return;
            }
        }
    }


    protected function _checkLastRun(): bool
    {
        $justRun = $this->data->task->log->select('id')
            ->where('request', '=', self::SELF_REQUEST)
            ->where('id', '!=', $this->_log['id'])
            ->beginWhereClause()
                ->where('startDate', '>', '-' . self::COOLOFF . ' seconds')
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

    protected function _lockNextTask(): ?array
    {
        $count = $this->data->task->queue->update([
                'lockDate' => 'now',
                'lockId' => $this->_log['id'],
                'status' => 'locked'
            ])
            ->where('lockDate', '=', null)
            ->where('lockId', '=', null)
            ->orderBy('queueDate ASC')
            ->limit(1)
            ->execute();

        if (!$count) {
            return null;
        }

        return $this->data->task->queue->select('id', 'request')
            ->where('lockId', '=', $this->_log['id'])
            ->orderBy('priority DESC', 'queueDate ASC')
            ->toRow();
    }

    protected function _checkCompleted(string $taskId): bool
    {
        $sleeps = [0.5, 1, 2, 3];
        $check = null;
        $progress = Cli::newSpinner();

        do {
            $sleep = array_shift($sleeps);
            $progress->waitFor($sleep);

            $check = $this->data->task->queue->select('id', 'request', 'status')
                ->where('id', '=', $taskId)
                ->toRow();

            if (!$check) {
                break;
            }
        } while (!empty($sleeps));

        $this->_updateLog('processing');


        // Task is still running
        if ($check !== null) {
            $progress->complete('still running', 'operative');
            return false;
        }

        $progress->complete('complete');
        return true;
    }

    protected function _afterDispatch(mixed $output): mixed
    {
        $this->_finalizeLog();
        return $output;
    }

    public function handleException(\Throwable $e)
    {
        Cli::writeErrorLine((string)$e);
        $this->_finalizeLog();

        Glitch::logException($e);
        parent::handleException($e);
    }

    protected function _finalizeLog()
    {
        try {
            $this->_updateLog('complete');
        } catch (\Exception $e) {
            Glitch::logException($e);
            $this->_log->delete();
        }
    }

    protected function _updateLog(string $status)
    {
        if (!$this->_log || $this->_log->isNew()) {
            return;
        }

        $output = (string)$this->_outputReceiver->getContents();
        $error = (string)$this->_errorReceiver->getContents();

        if (!strlen($output)) {
            $output = null;
        }

        if (!strlen($error)) {
            $error = null;
        }

        $this->_log->output = $output;
        $this->_log->errorOutput = $error;
        $this->_log->runTime = $this->_timer->getTime();
        $this->_log->status = $status;
        $this->_log->save();
    }
}
