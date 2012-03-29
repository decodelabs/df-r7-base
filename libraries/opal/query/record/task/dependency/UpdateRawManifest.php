<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task\dependency;

use df;
use df\core;
use df\opal;

class UpdateRawManifest extends Base {
    
    public function applyResolution(opal\query\record\task\ITask $dependentTask) {
        if($dependentTask instanceof opal\query\record\task\UpdateRaw) {
            $manifest = $this->_requiredTask->getRecord()->getPrimaryManifest();
            
            foreach($this->_parentFields as $key => $field) {
                break;
            }
            
            $values = array();
            
            foreach($manifest->toArray() as $key => $value) {
                $values[$field.'_'.$key] = $value;
            }
            
            $dependentTask->setValues($values);
        }
        
        return $this;
    }
    
    public function resolve(opal\query\record\task\ITaskSet $taskSet, opal\query\record\task\ITask $dependentTask) {
        core\stub($taskSet);
    }
}
