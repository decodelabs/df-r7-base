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

class UpdateRawKeySet extends mesh\job\Dependency implements opal\record\task\IParentFieldAwareDependency {

    use opal\record\task\TDependency;
    use opal\record\task\TParentFieldAwareDependency;

    public function __construct($parentFields, opal\record\task\ITask $requiredTask) {
        if(!is_array($parentFields)) {
            $parentFields = [$parentFields => $parentFields];
        }

        $this->_parentFields = $parentFields;
        $this->_requiredTask = $requiredTask;
    }

    public function applyResolution(opal\record\task\ITask $dependentTask) {
        if($dependentTask instanceof opal\record\task\UpdateRaw) {
            $keySet = $this->_requiredTask->getRecord()->getPrimaryKeySet();

            foreach($this->_parentFields as $key => $field) {
                break;
            }

            $values = [];

            foreach($keySet->toArray() as $key => $value) {
                $values[$field.'_'.$key] = $value;
            }

            $dependentTask->setValues($values);
        }

        return $this;
    }

    public function resolve(opal\record\task\ITaskSet $taskSet, opal\record\task\ITask $dependentTask) {
        core\stub($taskSet);
    }
}
