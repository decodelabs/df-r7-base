<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task;

use df;
use df\core;
use df\opal;

class TaskSet implements ITaskSet {
    
    protected $_tasks = array();
    protected $_transaction;
    
    public function __construct(core\IApplication $application=null) {
        if($application === null) {
            $application = df\Launchpad::$application;
        }
        
        $this->_transaction = new opal\query\Transaction($application);
    }
    
    
    public function getTransaction() {
        return $this->_transaction;
    }
    
    
    public function save(opal\query\record\IRecord $record) {
        if($record->isNew()) {
            return $this->insert($record);
        } else if($record->hasChanged()) {
            return $this->update($record);
        } else {
            return null;
        }
    }
    
    public function insert(opal\query\record\IRecord $record) {
        $task = new InsertRecord($record);
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            if(!$this->_tasks[$id] instanceof IInsertRecordTask) {
                throw new RuntimeException(
                    'Record '.$id.' has already been queued for a conflicting operation'
                );
            }
            
            return $this->_tasks[$id];
        }
        
        $this->addTask($task);
        return $task;
    }
    
    public function replace(opal\query\record\IRecord $record) {
        $task = new ReplaceRecord($record);
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            if(!$this->_tasks[$id] instanceof IReplaceRecordTask) {
                throw new RuntimeException(
                    'Record '.$id.' has already been queued for a conflicting operation'
                );
            }
            
            return $this->_tasks[$id];
        }
        
        $this->addTask($task);
        return $task;
    }
    
    public function update(opal\query\record\IRecord $record) {
        $task = new UpdateRecord($record);
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            if(!$this->_tasks[$id] instanceof IUpdateRecordTask) {
                throw new RuntimeException(
                    'Record '.$id.' has already been queued for a conflicting operation'
                );
            }
            
            return $this->_tasks[$id];
        }
        
        $this->addTask($task);
        return $task;
    }
    
    public function delete(opal\query\record\IRecord $record) {
        $task = new DeleteRecord($record);
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            if(!$this->_tasks[$id] instanceof IDeleteRecordTask) {
                throw new RuntimeException(
                    'Record '.$id.' has already been queued for a conflicting operation'
                );
            }
            
            return $this->_tasks[$id];
        }
        
        $this->addTask($task);
        return $task;
    }
    
    public function addTask(ITask $task) {
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            throw new RuntimeException(
                'Record '.$id.' has already been queued'
            );
        }
        
        $this->_transaction->registerAdapter($task->getAdapter());
        $this->_tasks[$id] = $task;
        return $this;
    }
    
    public function hasTask($id) {
        if($id instanceof ITask) {
            $id = $id->getId();
        }

        return isset($this->_tasks[$id]);
    }

    public function isRecordQueued(opal\query\record\IRecord $record) {
        $id = $record->getRecordAdapter()->getQuerySourceId().'#'.Base::extractRecordId($record);
        return isset($this->_tasks[$id]);
    }
    
    public function setRecordAsQueued(opal\query\record\IRecord $record) {
        $id = $record->getRecordAdapter()->getQuerySourceId().'#'.Base::extractRecordId($record);

        if(!isset($this->_tasks[$id])) {
            $this->_tasks[$id] = false;
        }

        return $this;
    }
    
    
    
    public function execute() {
        $this->_tasks = array_filter($this->_tasks);
        
        uasort($this->_tasks, function($taskA, $taskB) {
            return $taskA->countDependencies() > $taskB->countDependencies();
        });


        try {
            while(!empty($this->_tasks)) {
                $task = array_shift($this->_tasks);
                
                if(!$task) {
                    continue;
                }
                
                $task->resolveDependencies($this);
                $task->execute($this->_transaction);

                if($task->applyResolutionToDependants()) {
                    uasort($this->_tasks, function($taskA, $taskB) {
                        return $taskA->countDependencies() > $taskB->countDependencies();
                    });
                }
            }
        } catch(\Exception $e) {
            $this->_transaction->rollback();
            throw $e;
        }
        
        $this->_transaction->commit();
        return $this;
    }
}
