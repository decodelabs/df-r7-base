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

class BridgeResolution implements mesh\job\IResolution {

    protected $_targetField;

    protected $_queue;

    public function __construct(string $targetField) {
        $this->_targetField = $targetField;
    }

    public function untangle(mesh\job\IQueue $queue, mesh\job\IJob $subordinate, mesh\job\IJob $dependency): bool {
        $this->_queue = $queue;

        return false;
    }

    public function resolve(mesh\job\IJob $subordinate, mesh\job\IJob $dependency) {
        if($subordinate instanceof opal\record\IJob) {
            $record = $subordinate->getRecord();
            $keySet = $dependency->getRecord()->getPrimaryKeySet();

            if((string)$keySet != (string)$record->getRawId($this->_targetField)) {
                $record->set($this->_targetField, $keySet);
            } else {
                $record->markAsChanged($this->_targetField);
            }
        }

        return $this;
    }
}
