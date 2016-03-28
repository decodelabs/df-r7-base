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
    protected $_ignore = [];
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



// DELETE ME
    public function addRawQuery($id, $query) {
        return $this->asap($id, $query);
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

            if($adapter === null && $callback instanceof ITransactionAdapterProvider) {
                $adapter = $callback->getTransactionAdapter();
            }

            $job = new Generic($id, $callback, $adapter);
        }

        $this->addJob($job);
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


    public function addJob(IJob $job) {
        $id = $job->getId();

        if($adapter = $job->getAdapter()) {
            $this->_transaction->registerAdapter($adapter);
        }

        $this->_jobs[] = $job;

        if($this->_isExecuting && $job instanceof IEventBroadcastingJob) {
            $job->reportPreEvent($this);
        }

        return $this;
    }

    public function hasJob($id): bool {
        if($id instanceof IJob) {
            $id = $id->getId();
        }

        foreach($this->_jobs as $job) {
            if($job->getId() == $id) {
                return true;
            }
        }

        return false;
    }

    public function hasJobUsing($object): bool {
        $id = $this->getObjectId($object);

        foreach($this->_jobs as $job) {
            if($job->getObjectId() == $id) {
                return true;
            }
        }

        return false;
    }

    public function getJob($id) {
        if($id instanceof IJob) {
            $id = $id->getId();
        }

        foreach($this->_jobs as $job) {
            if($job->getId() == $id) {
                return $job;
            }
        }
    }

    public function getJobsUsing($object): array {
        $output = [];
        $id = $this->getObjectId($object);

        foreach($this->_jobs as $job) {
            if($job->getObjectId() == $id) {
                $output[] = $job;
            }
        }

        return $output;
    }

    public function getLastJobUsing($object) {
        $id = $this->getObjectId($object);

        foreach(array_reverse($this->_jobs) as $job) {
            if($job->getObjectId() == $id) {
                return $job;
            }
        }
    }



// Objects
    public function ignore($object) {
        $id = $this->getObjectId($object);

        if(isset($this->_ignore[$id])) {
            $this->_ignore[$id]++;
        } else {
            $this->_ignore[$id] = 1;
        }

        return $this;
    }

    public function unignore($object) {
        $id = $this->getObjectId($object);

        if(isset($this->_ignore[$id]) && $this->_ignore[$id] > 1) {
            $this->_ignore[$id]--;
        } else {
            unset($this->_ignore[$id]);
        }

        return $this;
    }

    public function forget($object) {
        $id = $this->getObjectId($object);
        unset($this->_ignore[$id]);
        return $this;
    }

    public function isIgnored($object): bool {
        $id = $this->getObjectId($object);
        return isset($this->_ignore[$id]);
    }


    public function isDeployed($object): bool {
        if($object instanceof IJob) {
            return $this->hasJob($object);
        }

        $id = $this->getObjectId($object);

        if(isset($this->_ignore[$id])) {
            return true;
        }

        foreach($this->_jobs as $job) {
            if($job->getObjectId() == $id) {
                return true;
            }
        }

        return false;
    }



    public static function getObjectId($object): string {
        if($object instanceof IJob) {
            return $object->getObjectId();
        }

        if(is_scalar($object)) {
            return (string)$object;
        /*
        } else if($object instanceof mesh\entity\ILocatorProvider) {
            return (string)$object->getEntityLocator();
        */
        } else {
            return spl_object_hash($object);
        }
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
            if($job instanceof IEventBroadcastingJob) {
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

                if($job instanceof IEventBroadcastingJob) {
                    $job->reportExecuteEvent($this);
                }

                $job->execute();

                if($job->resolveSubordinates()) {
                    $this->_sortJobs();
                }

                if($job instanceof IEventBroadcastingJob) {
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