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

    public function addTask(mesh\job\IJob $job) {
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

        if($this->_isExecuting && $job instanceof mesh\job\IEventBroadcastingJob) {
            $job->reportPreEvent($this);
        }

        return $this;
    }

    public function hasTask($id) {
        if($id instanceof mesh\job\IJob) {
            $id = $id->getId();
        }

        return isset($this->_jobs[$id]);
    }

    public function getTask($id) {
        if($id instanceof mesh\job\IJob) {
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
}
