<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df\axis;
use df\core;
use df\flex;
use df\opal;

class Json extends Base implements opal\schema\ILargeByteSizeRestrictedField
{
    use opal\schema\TField_LargeByteSizeRestricted;

    protected function _init($size = null)
    {
        if ($size === null) {
            $size = opal\schema\IFieldSize::LARGE;
        }

        $this->setExponentSize($size);
    }


    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        $value = null;

        if (isset($row[$key])) {
            $value = flex\Json::fromString($row[$key]);
        }

        return $this->sanitizeValue($value, $forRecord);
    }

    public function deflateValue($value)
    {
        $value = $this->sanitizeValue($value);

        if ($value === null) {
            return null;
        }

        if ($value->isEmpty() && !$value->hasValue() && $this->isNullable()) {
            return null;
        } else {
            return flex\Json::toString($value);
        }
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if (!$value instanceof core\collection\ITree) {
            if (empty($value)) {
                $value = null;
            }

            if (!($value === null && $this->isNullable())) {
                if (is_string($value)) {
                    $value = flex\Json::fromString($value);
                } else {
                    $value = new core\collection\Tree($value);
                }
            }
        }

        return $value;
    }


    public function compareValues($value1, $value2)
    {
        return $this->deflateValue($value1) === $this->deflateValue($value2);
    }


    // TODO: validate default value


    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return new opal\schema\Primitive_Blob($this, $this->_exponentSize);
    }


    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setLargeByteSizeRestrictedStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getLargeByteSizeRestrictedStorageArray()
        );
    }
}
