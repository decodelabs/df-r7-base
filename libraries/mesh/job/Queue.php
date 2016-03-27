<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\job;

use df;
use df\core;
use df\mesh;

class Queue implements IQueue {

    protected $_jobs = [];
    protected $_transaction;
    protected $_isExecuting = false;

    public function __construct() {
        $this->_transaction = new Transaction();
    }


// Transaction
    public function getTransaction(): ITransaction {
        return $this->_transaction;
    }

    public function registerAdapter(ITransactionAdapter $adapter) {
        $this->_transaction->registerAdapter($adapter);
        return $this;
    }


// Jobs
    public function asap(...$args): IJob {
        $id = uniqid();
        $adapter = $callback = $job = null;

        foreach($args as $arg) {
            if($arg instanceof IJob) {
                $job = $arg;
                break;
            } else if(is_string($arg)) {
                $id = $arg;
            } else if($arg instanceof IJobAdapter) {
                $adapter = $arg;
            } else if(is_callable($arg)) {
                $callback = $arg;
            }
        }

        if(!$job) {
            if($callback === null) {
                throw new InvalidArgumentException(
                    'Generic jobs must have a callback'
                );
            }

            $job = new Generic($id, $callback, $adapter);
        }

        $this->addTask($job);
        return $job;
    }

    public function after(IJob $job, ...$args): IJob {
        return $this->asap(...$args)->addDependency($job);
    }

    public function emitEventAfter(IJob $job, $entity, $action, array $data=null): IJob {
        return $this->after($job, function() use($entity, $action, $data, $job) {
            mesh\Manager::getInstance()->emitEvent($entity, $action, $data, $this, $job);
        });
    }


// Runner
    public function execute() {
        if($this->_isExecuting) {
            return $this;
        }

        $this->_isExecuting = true;
        $this->_transaction->begin();
        $this->_jobs = array_filter($this->_jobs);

        foreach($this->_jobs as $job) {
            if($job instanceof mesh\job\IEventBroadcastingJob) {
                $job->reportPreEvent($this);
            }
        }

        $this->_sortJobs();

        try {
            while(!empty($this->_jobs)) {
                $job = array_shift($this->_jobs);

                if(!$job) {
                    continue;
                }

                $job->untangleDependencies($this);

                if($job instanceof mesh\job\IEventBroadcastingJob) {
                    $job->reportExecuteEvent($this);
                }

                $job->execute();

                if($job->resolveSubordinates()) {
                    $this->_sortJobs();
                }

                if($job instanceof mesh\job\IEventBroadcastingJob) {
                    $job->reportPostEvent($this);
                }
            }
        } catch(\Exception $e) {
            $this->_transaction->rollback();
            throw $e;
        }

        $this->_transaction->commit();
        $this->_isExecuting = false;

        return $this;
    }

    protected function _sortJobs() {
        uasort($this->_jobs, function($taskA, $taskB) {
            $aCount = $taskA ? $taskA->countDependencies() : 0;
            $bCount = $taskB ? $taskB->countDependencies() : 0;

            return $aCount > $bCount;
        });
    }
}