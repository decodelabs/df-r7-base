<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\field;

use df\axis;
use df\core;
use df\opal;

class Duration extends Base implements opal\schema\ISignedField
{
    use opal\schema\TField_Signed;

    protected function _init()
    {
        $this->_isUnsigned = true;
    }

    public function compareValues($value1, $value2)
    {
        if ($value1 === null || $value2 === null) {
            return $value1 === null && $value2 === null;
        }

        return core\time\Duration::factory($value1)->getSeconds() == core\time\Duration::factory($value2)->getSeconds();
    }

// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (isset($row[$key])) {
            return new core\time\Duration($row[$key]);
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

        return $value->getSeconds();
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if ($value === '') {
            if ($this->isNullable()) {
                return null;
            } else {
                $value = 0;
            }
        }

        if ($value === null) {
            if ($this->isNullable()) {
                return null;
            } elseif (!empty($this->_defaultValue)) {
                $value = $this->_defaultValue;
            } else {
                $value = 0;
            }
        }

        return core\time\Duration::factory($value);
    }


// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        $output = new opal\schema\Primitive_Float($this, null, null);

        if ($this->_isUnsigned) {
            $output->isUnsigned(true);
        }

        return $output;
    }

// Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setSignedStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getSignedStorageArray()
        );
    }
}
