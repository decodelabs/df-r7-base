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

class TaskLaunchQueued extends arch\task\Action {
    
    protected $_log;
    protected $_channel;
    protected $_entry;
    protected $_timer;

    protected function _beforeDispatch() {
        $this->_channel = (new core\io\channel\Memory('', 'text/plain'))->setId('buffer');
        $this->response->addChannel($this->_channel);
    }

    public function execute() {
        $this->_entry = $this->data->fetchForAction(
            'axis://task/Queue',
            $this->request->query['id']
        );

        if(!$environmentMode = $this->_entry['environmentMode']) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }

        $this->_log = $this->data->task->log->newRecord([
                'request' => $this->_entry['request'],
                'environmentMode' => $environmentMode
            ])
            ->save();

        if(!$this->_entry['lockDate'] || !$this->_entry['lockId']) {
            $this->_entry->lockDate = 'now';
            $this->_entry->lockId = $this->_log['id'];
            $this->_entry->save();
        }

        $this->_timer = new core\time\Timer();
        $this->task->launch($this->_entry['request'], $this->response, $this->_entry['environmentMode']);
    }

    protected function _afterDispatch($output) {
        $this->_finalizeLog();
        $this->_entry->delete();
    }

    public function handleException(\Exception $e) {
        $context = new core\debug\Context();
        $context->exception($e);
        $exception = (new core\debug\renderer\PlainText($context))->render();
        $this->_channel->writeError($exception);
        $this->_finalizeLog();

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