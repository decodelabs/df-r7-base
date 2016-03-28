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

// Exceptions
interface IException extends opal\record\IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IParentFieldAwareDependency extends mesh\job\IDependency {
    public function getParentFields();
}

trait TParentFieldAwareDependency {

    protected $_parentFields = [];

    public function getParentFields() {
        return $this->_parentFields;
    }
}


interface IKeyTask extends mesh\job\IJob {
    public function setKeys(array $keys);
    public function addKeys(array $keys);
    public function addKey($key, $value);
    public function getKeys();
}

interface IFilterKeyTask extends mesh\job\IJob {
    public function setFilterKeys(array $keys);
    public function addFilterKeys(array $keys);
    public function addFilterKey($key, $value);
    public function getFilterKeys();
}


interface IRecordTask extends mesh\job\IJob, mesh\job\IEventBroadcastingJob {

    const EVENT_PRE = 'pre';
    const EVENT_EXECUTE = 'execute';
    const EVENT_POST = 'post';

    public function getRecord();
    public function getRecordJobName();
}

trait TRecordTask {

    protected $_record;

    public function getObjectId(): string {
        return mesh\job\Queue::getObjectId($this->_record);
    }

    public function getRecord() {
        return $this->_record;
    }

    public function getAdapter() {
        return $this->_record->getAdapter();
    }

    public function reportPreEvent(mesh\job\IQueue $queue) {
        $this->_record->triggerJobEvent($queue, $this, IRecordTask::EVENT_PRE);
        return $this;
    }

    public function reportExecuteEvent(mesh\job\IQueue $queue) {
        $this->_record->triggerJobEvent($queue, $this, IRecordTask::EVENT_EXECUTE);
        return $this;
    }

    public function reportPostEvent(mesh\job\IQueue $queue) {
        $this->_record->triggerJobEvent($queue, $this, IRecordTask::EVENT_POST);
        return $this;
    }
}