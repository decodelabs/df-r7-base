<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use DecodeLabs\Dictum;
use df\axis;

use df\opal;

class Slug extends Base implements axis\schema\IAutoUniqueField
{
    use axis\schema\TAutoUniqueField;

    protected $_allowPathFormat = false;

    public function allowPathFormat(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_allowPathFormat) {
                $this->_hasChanged = true;
            }

            $this->_allowPathFormat = $flag;
            return $this;
        }

        return $this->_allowPathFormat;
    }


    // Values
    public function sanitizeClauseValue($value)
    {
        if ($value === null && $this->isNullable()) {
            return null;
        }

        return (string)$value;
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if ($value === null && $this->isNullable()) {
            return null;
        }

        if ($this->_allowPathFormat) {
            return Dictum::pathSlug($value, '~');
        } else {
            return Dictum::slug($value);
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
        return new opal\schema\Primitive_Varchar($this, 255);
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setAutoUniqueStorageArray($data);

        $this->_allowPathFormat = $data['apf'];
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getAutoUniqueStorageArray(),
            [
                'apf' => $this->_allowPathFormat
            ]
        );
    }
}
