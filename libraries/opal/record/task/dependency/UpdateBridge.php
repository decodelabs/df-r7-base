<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task\dependency;

use df;
use df\core;
use df\opal;
use df\mesh;

class UpdateBridge extends mesh\job\Dependency implements opal\record\task\IParentFieldAwareDependency {

    use opal\record\task\TParentFieldAwareDependency;

    public function __construct($parentFields, mesh\job\IJob $requiredJob) {
        if(!is_array($parentFields)) {
            $parentFields = [$parentFields => $parentFields];
        }

        $this->_parentFields = $parentFields;
        $this->_requiredJob = $requiredJob;
    }

    public function untangle(mesh\job\IQueue $taskSet, mesh\job\IJob $dependentTask) {
        /*
         * Need to create a new Update task for record in dependentTask to fill in missing
         * id when this record is inserted, then save it to taskSet
         */

        $record = $dependentTask->getRecord();
        $updateTask = new opal\record\task\UpdateRecord($record);
        $updateTask->addDependency($this);
        $taskSet->addJob($updateTask);

        return $this;
    }

    public function resolve(mesh\job\IJob $dependentTask) {
        if($dependentTask instanceof opal\record\task\IRecordTask) {
            $record = $dependentTask->getRecord();
            $keySet = $this->_requiredJob->getRecord()->getPrimaryKeySet();
            $field = current($this->_parentFields);

            $record->set($field, $keySet);
        }

        return $this;
    }
}
