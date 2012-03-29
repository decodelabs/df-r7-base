<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task\dependency;

use df;
use df\core;
use df\opal;

class Base implements IDependency {
    
    protected $_parentFields = array();
    protected $_requiredTask;
    
    public function __construct($parentFields, opal\query\record\task\ITask $requiredTask) {
        if(!is_array($parentFields)) {
            $parentFields = array($parentFields => $parentFields);
        }
        
        $this->_parentFields = $parentFields;
        $this->_requiredTask = $requiredTask;
    }
    
    public function getId() {
        return implode(',', $this->_parentFields).':'.$this->_requiredTask->getId();
    }
    
    public function getParentFields() {
        return $this->_parentFields;
    }
    
    public function getRequiredTask() {
        return $this->_requiredTask;
    }
    
    public function getRequiredTaskId() {
        return $this->_requiredTask->getId();
    }
    
    public function applyResolution(opal\query\record\task\ITask $dependentTask) {
        // Don't need to do anything :)
        return $this;
    }
    
    public function resolve(opal\query\record\task\ITaskSet $taskSet, opal\query\record\task\ITask $dependentTask) {
        /*
         * This one could be tricky - we don't know why it's not resolved yet.
         * Need to look through requiredTask's dependencies and try and resolve them
         */ 
        core\stub($this, $dependentTask, $taskSet);
    }
} 