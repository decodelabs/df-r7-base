<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\job;

use df;
use df\core;
use df\opal;
use df\mesh;

class InsertResolution implements mesh\job\IResolution {

    protected $_targetField;
    protected $_isForeign = false;

    protected $_queue;

    public function __construct(string $targetField, bool $isForeign=false) {
        $this->_targetField = $targetField;
        $this->_isForeign = $isForeign;
    }

    public function untangle(mesh\job\IQueue $queue, mesh\job\IJob $subordinate, mesh\job\IJob $dependency) {
        /*
         * Need to create a new Update task for record in subordinate to fill in missing
         * id when this record is inserted, then save it to queue
         */
        $queue->after($dependency, new Update($subordinate->getRecord()), $this)
            ->addDependency($subordinate);

        $this->_queue = $queue;

        return $this;
    }

    public function resolve(mesh\job\IJob $subordinate, mesh\job\IJob $dependency) {
        if($subordinate instanceof opal\record\IJob) {
            if($this->_isForeign) {
                $record = $dependency->getRecord();
                $keySet = $subordinate->getRecord()->getPrimaryKeySet();
            } else {
                $record = $subordinate->getRecord();
                $keySet = $dependency->getRecord()->getPrimaryKeySet();
            }

            if((string)$keySet != (string)$record->getRawId($this->_targetField)) {
                $record->set($this->_targetField, $keySet);
            } else {
                $record->markAsChanged($this->_targetField);
            }
        }

        return $this;
    }
}
