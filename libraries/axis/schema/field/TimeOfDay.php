<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\field;

use df\axis;
use df\core;
use df\opal;

class TimeOfDay extends Base
{
    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (isset($row[$key])) {
            return new core\time\TimeOfDay($row[$key]);
        } else {
            return null;
        }
    }

    public function deflateValue($value)
    {
        $value = $this->sanitizeValue($value);

        if (empty($value)) {
            return null;
        }

        return $value->toString();
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if (empty($value)) {
            if ($this->isNullable()) {
                return null;
            } elseif (!empty($this->_defaultValue)) {
                $value = $this->_defaultValue;
            } else {
                $value = '00:00:00';
            }
        }

        return core\time\TimeOfDay::factory($value);
    }

    public function compareValues($value1, $value2)
    {
        return (string)$value1 === (string)$value2;
    }


    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return new opal\schema\Primitive_Time($this);
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
    }

    public function toStorageArray()
    {
        return $this->_getBaseStorageArray();
    }
}
