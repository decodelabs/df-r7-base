<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df\axis;
use df\opal;

class Binary extends Base implements
    axis\schema\ILengthRestrictedField,
    opal\schema\ILargeByteSizeRestrictedField
{
    use axis\schema\TLengthRestrictedField;
    use opal\schema\TField_LargeByteSizeRestricted;

    protected function _init($length = null)
    {
        if (is_string($length)) {
            $this->setExponentSize($length);
        } else {
            $this->setLength($length);
        }
    }

    public function compareValues($value1, $value2)
    {
        return (string)$value1 === (string)$value2;
    }

    public function getSearchFieldType()
    {
        return 'string';
    }

    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        if ($this->_exponentSize !== null) {
            return new opal\schema\Primitive_Blob($this, $this->_exponentSize);
        } elseif ($this->_isConstantLength) {
            return new opal\schema\Primitive_Binary($this, $this->_length);
        } else {
            return new opal\schema\Primitive_Varbinary($this, $this->_length);
        }
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setLengthRestrictedStorageArray($data);
        $this->_setLargeByteSizeRestrictedStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getLengthRestrictedStorageArray(),
            $this->_getLargeByteSizeRestrictedStorageArray()
        );
    }
}
