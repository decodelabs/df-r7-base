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
    public function addGenericTask($a, $b=null, $c=null);

    public function addTask(ITask $task);
    public function hasTask($id);
    public function getTask($id);
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
        //core\stub($this->_requiredTask, $dependentTask, $taskSet);
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
    
    public function execute(opal\query\ITransaction $transaction);
}



trait TTask {

    protected $_id;
    protected $_dependencies = array();
    protected $_dependants = array();

    protected function _setId($id) {
        $adapter = $this->getAdapter();

        if($adapter) {
            $prefix = $adapter->getQuerySourceId();
        } else {
            $prefix = uniqid();
        }

        $this->_id = $prefix.'#'.$id;
    }
    
    public function getId() {
        return $this->_id;
    }

    
// Dependencies
    public function addDependency($dependency) {
        if($dependency instanceof opal\record\task\ITask) {
            $dependency = new opal\record\task\dependency\Generic($dependency);
        } else if(!$dependency instanceof opal\record\task\IDependency) {
            throw new InvalidArgumentException('Invalid dependency');
        }

            
        $id = $dependency->getId();

        if(isset($this->_dependencies[$id])) {
            return $this;
        }
        
        $this->_dependencies[$id] = $dependency;
        $dependency->getRequiredTask()->addDependant($this);
        
        return $this;
    }

    public function countDependencies() {
        return count($this->_dependencies);
    }

    public function hasDependencies() {
        return !empty($this->_dependencies);
    }
    
    public function resolveDependencies(ITaskSet $taskSet) {
        while(!empty($this->_dependencies)) {
            $dependency = array_shift($this->_dependencies);
            $dependency->resolve($taskSet, $this);
        }
        
        return $this;
    }
    
    public function applyDependencyResolution(ITask $dependencyTask) {
        $taskId = $dependencyTask->getId();
        
        foreach($this->_dependencies as $id => $dependency) {
            if($taskId == $dependency->getRequiredTaskId()) {
                $dependency->applyResolution($this);
                unset($this->_dependencies[$id]);
            }
        }
        
        return $this;
    }
    
    
// Dependants
    public function addDependant(ITask $task) {
        $this->_dependants[$task->getId()] = $task;
        return $this;
    }

    public function countDependants() {
        return count($this->_dependants);
    }

    public function hasDependants() {
        return !empty($this->_dependants);
    }
    
    public function applyResolutionToDependants() {
        $output = false;

        while(!empty($this->_dependants)) {
            $output = true;
            $task = array_shift($this->_dependants);
            $task->applyDependencyResolution($this);
        }

        return $output;
    }
}


trait TAdapterAwareTask {

    protected $_adapter;

    public function getAdapter() {
        return $this->_adapter;
    }
}


interface IEventBroadcastingTask extends ITask {
    public function reportPreEvent(ITaskSet $taskSet);
    public function reportExecuteEvent(ITaskSet $taskSet);
    public function reportPostEvent(ITaskSet $taskSet);
}


interface IOptionalAdapterAwareTask extends ITask {
    public function hasAdapter();
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
        return $this->_record->getRecordAdapter();
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
    public function ifNotExists($flag=null);
}

interface IReplaceRecordTask extends IRecordTask, IReplaceTask {}
interface IUpdateRecordTask extends IRecordTask, IUpdateTask {}
interface IDeleteRecordTask extends IRecordTask, IDeleteTask {}
