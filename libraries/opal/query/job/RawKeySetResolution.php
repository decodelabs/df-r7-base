<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\job;

use df\mesh;
use df\opal;

class RawKeySetResolution implements mesh\job\IResolution
{
    protected $_targetField;

    public function __construct($targetField)
    {
        $this->_targetField = $targetField;
    }

    public function untangle(mesh\job\IQueue $queue, mesh\job\IJob $subordinate, mesh\job\IJob $dependency): bool
    {
        return false;
    }

    public function resolve(mesh\job\IJob $subordinate, mesh\job\IJob $dependency)
    {
        if (
            !$subordinate instanceof opal\query\job\Update ||
            !$dependency instanceof opal\record\IJob
        ) {
            return $this;
        }

        $keySet = $dependency->getRecord()->getPrimaryKeySet();
        $values = [];

        foreach ($keySet->toArray() as $key => $value) {
            $values[$this->_targetField . '_' . $key] = $value;
        }

        $subordinate->setValues($values);
        return $this;
    }
}
