<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;
use df\mesh;

class TaskSet extends mesh\job\Queue implements ITaskSet {

    public function __construct() {
        $this->_transaction = new opal\query\Transaction();
    }

    public function save(opal\record\IRecord $record) {
        if($record->isNew()) {
            return $this->insert($record);
        } else if($record->hasChanged()) {
            return $this->update($record);
        } else {
            return null;
        }
    }

    public function insert(opal\record\IRecord $record) {
        $job = new InsertRecord($record);
        $id = $job->getId();

        if(isset($this->_jobs[$id])) {
            if($this->_jobs[$id] === false) {
                unset($this->_jobs[$id]);
            } else {
                if(!$this->_jobs[$id] instanceof IInsertRecordTask) {
                    throw new RuntimeException(
                        'Record '.$id.' has already been queued for a conflicting operation'
                    );
                }

                return $this->_jobs[$id];
            }
        }

        $this->addTask($job);
        return $job;
    }

    public function replace(opal\record\IRecord $record) {
        $job = new ReplaceRecord($record);
        $id = $job->getId();

        if(isset($this->_jobs[$id])) {
            if($this->_jobs[$id] === false) {
                unset($this->_jobs[$id]);
            } else {
                if(!$this->_jobs[$id] instanceof IReplaceRecordTask) {
                    throw new RuntimeException(
                        'Record '.$id.' has already been queued for a conflicting operation'
                    );
                }

                return $this->_jobs[$id];
            }
        }

        $this->addTask($job);
        return $job;
    }

    public function update(opal\record\IRecord $record) {
        $job = new UpdateRecord($record);
        $id = $job->getId();

        if(isset($this->_jobs[$id])) {
            if($this->_jobs[$id] === false) {
                unset($this->_jobs[$id]);
            } else {
                if(!$this->_jobs[$id] instanceof IUpdateRecordTask) {
                    throw new RuntimeException(
                        'Record '.$id.' has already been queued for a conflicting operation'
                    );
                }

                return $this->_jobs[$id];
            }
        }

        $this->addTask($job);
        return $job;
    }

    public function delete(opal\record\IRecord $record) {
        $job = new DeleteRecord($record);
        $id = $job->getId();

        if(isset($this->_jobs[$id])) {
            if($this->_jobs[$id] === false) {
                unset($this->_jobs[$id]);
            } else {
                if(!$this->_jobs[$id] instanceof IDeleteRecordTask) {
                    throw new RuntimeException(
                        'Record '.$id.' has already been queued for a conflicting operation'
                    );
                }

                return $this->_jobs[$id];
            }
        }

        $this->addTask($job);
        return $job;
    }

    public function addRawQuery($id, opal\query\IWriteQuery $query) {
        $job = new RawQuery($id, $query);
        $this->addTask($job);

        return $job;
    }

    public function addGenericTask(...$args) {
        $id = uniqid();
        $adapter = null;
        $callback = null;

        foreach($args as $arg) {
            if(is_string($arg)) {
                $id = $arg;
            } else if($arg instanceof opal\query\IAdapter) {
                $adapter = $arg;
            } else if(is_callable($arg)) {
                $callback = $arg;
            }
        }

        if($callback === null) {
            throw new InvalidArgumentException(
                'Generic jobs must have a callback'
            );
        }

        $job = new Generic($id, $callback, $adapter);
        $this->addTask($job);

        return $job;
    }

    public function after(ITask $job, ...$args) {
        return $this->addGenericTask(...$args)->addDependency($job);
    }

    public function emitEventAfter(ITask $job, $entity, $action, array $data=null) {
        return $this->addGenericTask(function() use($entity, $action, $data, $job) {
                mesh\Manager::getInstance()->emitEvent($entity, $action, $data, $this, $job);
            })
            ->addDependency($job);
    }



    public function addTask(ITask $job) {
        $id = $job->getId();

        if(isset($this->_jobs[$id])) {
            return $this;

            /*
            throw new RuntimeException(
                'Record '.$id.' has already been queued'
            );
            */
        }

        if($adapter = $job->getAdapter()) {
            $this->_transaction->registerAdapter($adapter);
        }

        $this->_jobs[$id] = $job;

        if($this->_isExecuting && $job instanceof IEventBroadcastingTask) {
            $job->reportPreEvent($this);
        }

        return $this;
    }

    public function hasTask($id) {
        if($id instanceof ITask) {
            $id = $id->getId();
        }

        return isset($this->_jobs[$id]);
    }

    public function getTask($id) {
        if($id instanceof ITask) {
            $id = $id->getId();
        } else if($id instanceof opal\record\IRecord) {
            $id = $this->_getRecordId($id);
        }

        if(isset($this->_jobs[$id])) {
            return $this->_jobs[$id];
        }
    }

    public function isRecordQueued(opal\record\IRecord $record) {
        $id = $this->_getRecordId($record);
        return isset($this->_jobs[$id]);
    }

    public function setRecordAsQueued(opal\record\IRecord $record) {
        $id = $this->_getRecordId($record);

        if(!isset($this->_jobs[$id])) {
            $this->_jobs[$id] = false;
        }

        return $this;
    }

    protected function _getRecordId(opal\record\IRecord $record) {
        return $record->getAdapter()->getQuerySourceId().'#'.opal\record\Base::extractRecordId($record);
    }

    public function execute() {
        if($this->_isExecuting) {
            return $this;
        }

        $this->_isExecuting = true;
        $this->_jobs = array_filter($this->_jobs);

        foreach($this->_jobs as $job) {
            if($job instanceof IEventBroadcastingTask) {
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

                if($job instanceof IEventBroadcastingTask) {
                    $job->reportExecuteEvent($this);
                }

                $job->execute($this->_transaction);

                if($job->resolveSubordinates()) {
                    $this->_sortJobs();
                }

                if($job instanceof IEventBroadcastingTask) {
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
}
