<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task\dependency;

use df;
use df\core;
use df\opal;

class UpdateManifestField implements opal\record\task\IParentFieldAwareDependency {
    
    use opal\record\task\TDependency;
    use opal\record\task\TParentFieldAwareDependency;

    public function __construct($parentFields, opal\record\task\ITask $requiredTask) {
        if(!is_array($parentFields)) {
            $parentFields = array($parentFields => $parentFields);
        }
        
        $this->_parentFields = $parentFields;
        $this->_requiredTask = $requiredTask;
    }
    
    public function applyResolution(opal\record\task\ITask $dependentTask) {
        if($dependentTask instanceof opal\record\task\IRecordTask) {
            $record = $dependentTask->getRecord();
            $manifest = $this->_requiredTask->getRecord()->getPrimaryManifest();
            
            foreach($this->_parentFields as $key => $field) {
                $record->set($field, $manifest);
            }
        }
        
        return $this;
    }
    
    public function resolve(opal\record\task\ITaskSet $taskSet, opal\record\task\ITask $dependentTask) {
        /*
         * Need to create a new Update task for record in dependentTask to fill in missing
         * id when this record is inserted, then save it to taskSet
         */
        
        $record = $dependentTask->getRecord();
        $updateTask = new opal\record\task\UpdateRecord($record);
        $updateTask->addDependency($this);
        $taskSet->addTask($updateTask);
        
        return $this;
    }
}
