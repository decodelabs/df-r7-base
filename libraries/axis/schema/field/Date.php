<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df\axis;
use df\core;
use df\opal;

class Date extends Base implements axis\schema\IDateField
{
    protected $_includeTime = false;

    protected function _initAsTime()
    {
        $this->shouldIncludeTime(true);
    }

    public function shouldIncludeTime(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_includeTime) {
                $this->_hasChanged = true;
            }

            $this->_includeTime = $flag;
            return $this;
        }

        return $this->_includeTime;
    }

    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (isset($row[$key])) {
            return core\time\Date::factory($row[$key], null, $this->_includeTime);
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

        return $value->format(
            $this->_includeTime ?
                core\time\Date::DB :
                core\time\Date::DBDATE
        );
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if (empty($value)) {
            if ($this->isNullable()) {
                return null;
            } elseif (!empty($this->_defaultValue)) {
                $value = $this->_defaultValue;
            } else {
                $value = 'now';
            }
        }

        $value = core\time\Date::factory($value, null, $this->_includeTime);
        $value->toUtc();

        if (!$this->_includeTime) {
            $value->modify('00:00:00');
        }

        return $value;
    }

    public function compareValues($value1, $value2)
    {
        if ($value1 === null || $value2 === null) {
            return $value1 === $value2;
        }

        $value1 = core\time\Date::factory($value1, null, $this->_includeTime);
        $value2 = core\time\Date::factory($value2, null, $this->_includeTime);

        return $value1->eq($value2);
    }


    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        if ($this->_includeTime) {
            return new opal\schema\Primitive_DateTime($this);
        } else {
            return new opal\schema\Primitive_Date($this);
        }
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);

        if (isset($data['tim'])) {
            $this->_includeTime = $data['tim'];
        } else {
            $this->_includeTime = false;
        }
    }

    public function toStorageArray()
    {
        $output = $this->_getBaseStorageArray();

        if ($this->_includeTime) {
            $output['tim'] = true;
        }

        return $output;
    }
}
