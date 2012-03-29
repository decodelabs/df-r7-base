<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task;

use df;
use df\core;
use df\opal;

abstract class Base implements ITask {
    
    protected $_id;
    protected $_dependencies = array();
    protected $_dependants = array();
    
    public static function extractRecordId($record) {
        $manifest = null;
        $isRecord = false;
        
        if($record instanceof opal\query\record\IRecord) {
            $isRecord = true;
            $manifest = $record->getPrimaryManifest();
        } else if($record instanceof opal\query\record\IPrimaryManifest) {
            $manifest = $record;
        }
        
        if($manifest && !$manifest->isNull()) {
            return $manifest->getCombinedId();
        }
        
        if($isRecord) {
            return spl_object_hash($record);
        }
        
        if(is_array($record)) {
            return implode(opal\query\record\PrimaryManifest::COMBINE_SEPARATOR, $record);
        }
        
        return (string)$record;
    }
    
    
    public function __construct($id) {
        $this->_id = $this->getAdapter()->getQuerySourceId().'#'.$id;
    }
    
    
    public function getId() {
        return $this->_id;
    }

    
// Dependencies
    public function addDependency(opal\query\record\task\dependency\IDependency $dependency) {
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
    
    public function applyResolutionToDependants() {
        while(!empty($this->_dependants)) {
            $task = array_shift($this->_dependants);
            $task->applyDependencyResolution($this);
        }
    }
    
    
// Execute
    public function execute(opal\query\ITransaction $transaction) {
        core\stub($taskSet);
    }
}
