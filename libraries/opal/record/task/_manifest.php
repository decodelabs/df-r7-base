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
interface ITaskSet extends mesh\job\IQueue {
    public function save(opal\record\IRecord $record);
    public function insert(opal\record\IRecord $record);
    public function replace(opal\record\IRecord $record);
    public function update(opal\record\IRecord $record);
    public function delete(opal\record\IRecord $record);

    public function addRawQuery($id, opal\query\IWriteQuery $query);
    public function addGenericTask(...$args);
    public function after(ITask $task, ...$args);
    public function emitEventAfter(ITask $task, $entity, $action, array $data=null);

    public function addTask(ITask $task);
    public function hasTask($id);
    public function getTask($id);
    public function isRecordQueued(opal\record\IRecord $record);
    public function setRecordAsQueued(opal\record\IRecord $record);
}


interface IDependency extends mesh\job\IDependency {
    public function getRequiredTask();
    public function getRequiredTaskId();
    public function applyResolution(ITask $dependentTask);
    public function resolve(ITaskSet $taskSet, ITask $dependentTask);
}

trait TDependency {

    protected $_requiredTask;

    public function getRequiredTask() {
        return $this->_requiredTask;
    }

    public function getRequiredTaskId() {
        return $this->_requiredTask->getId();
    }

    public function applyResolution(opal\record\task\ITask $dependentTask) {
        return $this;
    }

    public function resolve(opal\record\task\ITaskSet $taskSet, opal\record\task\ITask $dependentTask) {
        //core\stub($this->_requiredTask, $dependentTask, $taskSet);
    }
}

interface IParentFieldAwareDependency extends IDependency {
    public function getParentFields();
}

trait TParentFieldAwareDependency {

    protected $_parentFields = [];

    public function getParentFields() {
        return $this->_parentFields;
    }
}




interface ITask extends mesh\job\IJob {
    public function addDependency($dependency);

    public function execute(opal\query\ITransaction $transaction);
}

interface IEventBroadcastingTask extends ITask {
    public function reportPreEvent(ITaskSet $taskSet);
    public function reportExecuteEvent(ITaskSet $taskSet);
    public function reportPostEvent(ITaskSet $taskSet);
}



interface IInsertTask extends ITask {}
interface IReplaceTask extends ITask {}
interface IUpdateTask extends ITask {}
interface IDeleteTask extends ITask {}



interface IKeyTask extends ITask {
    public function setKeys(array $keys);
    public function addKeys(array $keys);
    public function addKey($key, $value);
    public function getKeys();
}

interface IFilterKeyTask extends ITask {
    public function setFilterKeys(array $keys);
    public function addFilterKeys(array $keys);
    public function addFilterKey($key, $value);
    public function getFilterKeys();
}

interface IDeleteKeyTask extends IDeleteTask, IKeyTask, IFilterKeyTask {}


interface IRecordTask extends ITask, IEventBroadcastingTask {

    const EVENT_PRE = 'pre';
    const EVENT_EXECUTE = 'execute';
    const EVENT_POST = 'post';

    public function getRecord();
    public function getRecordTaskName();
}

trait TRecordTask {

    protected $_record;

    public function getRecord() {
        return $this->_record;
    }

    public function getAdapter() {
        return $this->_record->getAdapter();
    }

    public function reportPreEvent(ITaskSet $taskSet) {
        $this->_record->triggerTaskEvent($taskSet, $this, IRecordTask::EVENT_PRE);
        return $this;
    }

    public function reportExecuteEvent(ITaskSet $taskSet) {
        $this->_record->triggerTaskEvent($taskSet, $this, IRecordTask::EVENT_EXECUTE);
        return $this;
    }

    public function reportPostEvent(ITaskSet $taskSet) {
        $this->_record->triggerTaskEvent($taskSet, $this, IRecordTask::EVENT_POST);
        return $this;
    }
}

interface IInsertRecordTask extends IRecordTask, IInsertTask {
    public function ifNotExists(bool $flag=null);
}

interface IReplaceRecordTask extends IRecordTask, IReplaceTask {}
interface IUpdateRecordTask extends IRecordTask, IUpdateTask {}
interface IDeleteRecordTask extends IRecordTask, IDeleteTask {}
