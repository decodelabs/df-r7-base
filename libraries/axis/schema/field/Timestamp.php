<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df\axis;
use df\core;
use df\opal;

class Timestamp extends Base implements opal\schema\IAutoTimestampField
{
    use opal\schema\TField_AutoTimestamp;

    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (isset($row[$key])) {
            return core\time\Date::factory($row[$key]);
        } else {
            return null;
        }
    }

    public function deflateValue($value)
    {
        if (empty($value)) {
            $value = null;
        } elseif ($value instanceof core\time\IDate) {
            $value = $value->format(core\time\Date::DB);
        }

        return $value;
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if (!empty($value)) {
            $value = core\time\Date::factory($value);
            $value->toUtc();
        }

        return $value;
    }

    public function normalizeSavedValue($value, opal\record\IRecord $forRecord = null)
    {
        if ($this->_shouldTimestampOnUpdate || $value === null) {
            return new opal\record\valueContainer\LazyLoad($value, function ($value, $record, $fieldName) {
                return $record->getAdapter()->select($this->_name)
                    ->where('@primary', '=', $record->getPrimaryKeySet())
                    ->toValue($this->_name);
            });
        }

        return $value;
    }

    public function compareValues($value1, $value2)
    {
        return (string)$value1 === (string)$value2;
    }

    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        $output = new opal\schema\Primitive_Timestamp($this);
        $output->shouldTimestampOnUpdate($this->_shouldTimestampOnUpdate);
        $output->shouldTimestampAsDefault($this->_shouldTimestampAsDefault);

        return $output;
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setAutoTimestampStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getAutoTimestampStorageArray()
        );
    }
}
