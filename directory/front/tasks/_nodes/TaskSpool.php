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
    protected $_channel;
    protected $_timer;

    protected function _beforeDispatch()
    {
        $this->_channel = (new core\fs\MemoryFile('', 'text/plain'))->setId('buffer');
        $this->io->addChannel($this->_channel);

        $this->_log = $this->data->task->log->newRecord([
                'request' => self::SELF_REQUEST,
                'environmentMode' => df\Launchpad::$app->envMode
            ])
            ->save();
    }

    public function execute()
    {
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

        if ($justRun) {
            Cli::info('The spool task has already recently - please try again later');
            $this->_log->delete();
            return;
        }


        // Clear out old logs
        Cli::{'yellow'}('Clearing old logs: ');

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

        Cli::success('deleted '.$count.' entries');


        // Clear broken queue items
        $this->data->task->queue->delete()
            ->where('lockDate', '<', '-1 week')
            ->execute();


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

            $this->io->removeChannel($this->_channel);
            $this->runChild('tasks/launch-queued?id='.$taskId, false);
            $this->io->addChannel($this->_channel);
        }

        $this->data->task->queue->delete()
            ->where('id', 'in', array_keys($taskIds))
            ->execute();
    }

    protected function _afterDispatch($output)
    {
        $this->_finalizeLog();
        return $output;
    }

    public function handleException(\Throwable $e)
    {
        $this->_channel->writeError((string)$e);
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

        $output = $this->_channel->getContents();
        $error = $this->_channel->getErrorBuffer();

        if (!strlen($output)) {
            $output = null;
        }

        if (!strlen($error)) {
            $error = null;
        }

        $this->_log->output = $output;
        $this->_log->errorOutput = $error;
        $this->_log->runTime = $this->_timer->getTime();
        $this->_log->save();
    }
}
