<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task\dependency;

use df;
use df\core;
use df\opal;

class UpdateBridge extends Base {
    
    public function applyResolution(opal\query\record\task\ITask $dependentTask) {
        if($dependentTask instanceof opal\query\record\task\IRecordTask) {
            $record = $dependentTask->getRecord();
            $manifest = $this->_requiredTask->getRecord()->getPrimaryManifest()->toArray();
            
            foreach($this->_parentFields as $key => $field) {
                $record->set($field, $manifest[$key]);
            }
        }
        
        return $this;
    }
    
    public function resolve(opal\query\record\task\ITaskSet $taskSet, opal\query\record\task\ITask $dependentTask) {
        /*
         * Need to create a new Update task for record in dependentTask to fill in missing
         * id when this record is inserted, then save it to taskSet
         */
        
        $record = $dependentTask->getRecord();
        $updateTask = new opal\query\record\task\UpdateRecord($record);
        $updateTask->addDependency($this);
        $taskSet->addTask($updateTask);
        
        return $this;
    }
}
