<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class TaskSet implements ITaskSet {
    
    protected $_tasks = array();
    protected $_transaction;
    protected $_isExecuting = false;
    
    public function __construct(core\IApplication $application=null) {
        if($application === null) {
            $application = df\Launchpad::$application;
        }
        
        $this->_transaction = new opal\query\Transaction($application);
    }
    
    
    public function getTransaction() {
        return $this->_transaction;
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
        $task = new InsertRecord($record);
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            if($this->_tasks[$id] === false) {
                unset($this->_tasks[$id]);
            } else {
                if(!$this->_tasks[$id] instanceof IInsertRecordTask) {
                    throw new RuntimeException(
                        'Record '.$id.' has already been queued for a conflicting operation'
                    );
                }
                
                return $this->_tasks[$id];
            }
        }
        
        $this->addTask($task);
        return $task;
    }
    
    public function replace(opal\record\IRecord $record) {
        $task = new ReplaceRecord($record);
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            if($this->_tasks[$id] === false) {
                unset($this->_tasks[$id]);
            } else {
                if(!$this->_tasks[$id] instanceof IReplaceRecordTask) {
                    throw new RuntimeException(
                        'Record '.$id.' has already been queued for a conflicting operation'
                    );
                }
                
                return $this->_tasks[$id];
            }
        }
        
        $this->addTask($task);
        return $task;
    }
    
    public function update(opal\record\IRecord $record) {
        $task = new UpdateRecord($record);
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            if($this->_tasks[$id] === false) {
                unset($this->_tasks[$id]);
            } else {
                if(!$this->_tasks[$id] instanceof IUpdateRecordTask) {
                    throw new RuntimeException(
                        'Record '.$id.' has already been queued for a conflicting operation'
                    );
                }
                
                return $this->_tasks[$id];
            }
        }
        
        $this->addTask($task);
        return $task;
    }
    
    public function delete(opal\record\IRecord $record) {
        $task = new DeleteRecord($record);
        $id = $task->getId();
        
        if(isset($this->_tasks[$id])) {
            if($this->_tasks[$id] === false) {
                unset($this->_tasks[$id]);
            } else {
                if(!$this->_tasks[$id] instanceof IDeleteRecordTask) {
                    throw new RuntimeException(
                        'Record '.$id.' has already been queued for a conflicting operation'
                    );
                }
                
                return $this->_tasks[$id];
            }
        }
        
        $this->addTask($task);
        return $task;
    }

    public function addRawQuery($id, opal\query\IWriteQuery $query) {
        $task = new RawQuery($id, $query);
        $this->addTask($task);

        return $task;
    }

    public function addGenericTask($a, $b=null, $c=null) {
        $id = uniqid();
        $adapter = null;
        $callback = null;

        foreach(func_get_args() as $arg) {
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
                'Generic tasks must have a callback'
            );
        }

        $task = new Generic($id, $callback, $adapter);
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
        
        if($adapter = $task->getAdapter()) {
            $this->_transaction->registerAdapter($adapter);
        }

        $this->_tasks[$id] = $task;

        if($this->_isExecuting && $task instanceof IEventBroadcastingTask) {
            $task->reportPreEvent($this);
        }

        return $this;
    }
    
    public function hasTask($id) {
        if($id instanceof ITask) {
            $id = $id->getId();
        }

        return isset($this->_tasks[$id]);
    }

    public function getTask($id) {
        if($id instanceof ITask) {
            $id = $id->getId();
        }

        if(isset($this->_tasks[$id])) {
            return $tis->_tasks[$id];
        }
    }

    public function isRecordQueued(opal\record\IRecord $record) {
        $id = $record->getRecordAdapter()->getQuerySourceId().'#'.opal\record\Base::extractRecordId($record);
        return isset($this->_tasks[$id]);
    }
    
    public function setRecordAsQueued(opal\record\IRecord $record) {
        $id = $record->getRecordAdapter()->getQuerySourceId().'#'.opal\record\Base::extractRecordId($record);

        if(!isset($this->_tasks[$id])) {
            $this->_tasks[$id] = false;
        }

        return $this;
    }
    
    
    
    public function execute() {
        if($this->_isExecuting) {
            return $this;
        }

        $this->_isExecuting = true;
        $this->_tasks = array_filter($this->_tasks);
        
        foreach($this->_tasks as $task) {
            if($task instanceof IEventBroadcastingTask) {
                $task->reportPreEvent($this);
            }
        }
        
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

                if($task instanceof IEventBroadcastingTask) {
                    $task->reportPostEvent($this);
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
