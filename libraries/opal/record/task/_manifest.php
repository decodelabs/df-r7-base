<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

// Exceptions
interface IException extends opal\record\IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ITaskSet {
    public function getTransaction();
    public function save(opal\record\IRecord $record);
    public function insert(opal\record\IRecord $record);
    public function replace(opal\record\IRecord $record);
    public function update(opal\record\IRecord $record);
    public function delete(opal\record\IRecord $record);

    public function addRawQuery($id, opal\query\IWriteQuery $query);
    public function addGenericTask(opal\query\IAdapter $adapter, $id, Callable $callback);

    public function addTask(ITask $task);
    public function hasTask($id);
    public function isRecordQueued(opal\record\IRecord $record);
    public function setRecordAsQueued(opal\record\IRecord $record);
    public function execute();
}


interface IDependency {
    public function getId();
    public function getRequiredTask();
    public function getRequiredTaskId();
    public function applyResolution(ITask $dependentTask);
    public function resolve(ITaskSet $taskSet, ITask $dependentTask);
}

trait TDependency {

    protected $_idSalt;
    protected $_requiredTask;

    public function getId() {
        if($this->_idSalt === null) {
            $this->_idSalt = uniqid('_');
        }

        return $this->_requiredTask->getId().'|'.$this->_idSalt;
    }

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
        core\stub($this, $dependentTask, $taskSet);
    }
}

interface IParentFieldAwareDependency extends IDependency {
    public function getParentFields();
}

trait TParentFieldAwareDependency {

    protected $_parentFields = array();

    public function getParentFields() {
        return $this->_parentFields;
    }
}




interface ITask {
    public function getId();
    public function getAdapter();
    
    public function addDependency($dependency);
    public function countDependencies();
    public function hasDependencies();
    public function resolveDependencies(ITaskSet $taskSet);
    public function applyDependencyResolution(ITask $dependencyTask);

    public function countDependants();
    public function hasDependants();
    public function applyResolutionToDependants();
    
    public function reportPreEvent(ITaskSet $taskSet);
    public function reportPostEvent(ITaskSet $taskSet);

    public function execute(opal\query\ITransaction $transaction);
}


interface IInsertTask extends ITask {}
interface IReplaceTask extends ITask {}
interface IUpdateTask extends ITask {}
interface IDeleteTask extends ITask {}



interface IKeyTask extends ITask {
    public function getKeys();
}

interface IDeleteKeyTask extends IDeleteTask, IKeyTask {}


interface IRecordTask extends ITask {

    const EVENT_PRE = 'pre';
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
        return $this->_record->getRecordAdapter();
    }

    public function reportPreEvent(ITaskSet $taskSet) {
        $this->_record->triggerTaskEvent($taskSet, $this, IRecordTask::EVENT_PRE);
        return $this;
    }

    public function reportPostEvent(ITaskSet $taskSet) {
        $this->_record->triggerTaskEvent($taskSet, $this, IRecordTask::EVENT_POST);
        return $this;
    }
}

interface IInsertRecordTask extends IRecordTask, IInsertTask {}
interface IReplaceRecordTask extends IRecordTask, IReplaceTask {}
interface IUpdateRecordTask extends IRecordTask, IUpdateTask {}
interface IDeleteRecordTask extends IRecordTask, IDeleteTask {}
