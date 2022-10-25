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

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

class TaskLaunchQueued extends arch\node\Task
{
    protected $_log;
    protected $_entry;
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
    }

    public function execute(): void
    {
        $this->_entry = $this->data->fetchForAction(
            'axis://task/Queue',
            $this->request['id']
        );

        $this->_log = $this->data->task->log->newRecord([
                'request' => $this->_entry['request'],
                'environmentMode' => Genesis::$environment->getMode(),
                'status' => 'processing'
            ])
            ->save();

        if (!$this->_entry['lockDate'] || !$this->_entry['lockId']) {
            $this->_entry->lockDate = 'now';
            $this->_entry->lockId = $this->_log['id'];
        }

        $this->_entry->status = 'processing';
        $this->_entry->save();

        $this->_timer = new core\time\Timer();

        $this->task->launch(
            $this->_entry['request'],
            Cli::getSession(),
            null,
            false,
            false
        );
    }

    protected function _afterDispatch(mixed $output): mixed
    {
        $this->_finalizeLog();
        $this->_entry->delete();

        return $output;
    }

    public function handleException(\Throwable $e)
    {
        Cli::writeErrorLine((string)$e);
        $this->_finalizeLog();

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

        $this->_log->output = $output;
        $this->_log->errorOutput = $error;

        if ($this->_timer) {
            $this->_log->runTime = $this->_timer->getTime();
        }

        $this->_log->status = 'complete';
        $this->_log->save();
    }
}
