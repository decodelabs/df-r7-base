<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df\axis;
use df\opal;

class Set extends Base implements
    opal\schema\IOptionProviderField,
    opal\schema\ICharacterSetAwareField
{
    use opal\schema\TField_OptionProvider;
    use opal\schema\TField_CharacterSetAware;

    protected function _init($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } else {
            $this->setType($options);
        }
    }

    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        $value = null;

        if (isset($row[$key])) {
            $value = $row[$key];
        }

        if (!empty($value)) {
            $value = explode(',', $value);
        }

        return $this->sanitizeValue($value, $forRecord);
    }

    public function deflateValue($value)
    {
        $value = $this->sanitizeValue($value);

        if ($value === null) {
            return null;
        }

        return implode(',', $value);
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if ($value === null) {
            if ($this->isNullable()) {
                return null;
            } else {
                $value = [];
            }
        }

        if (!is_array($value)) {
            $value = [(string)$value];
        }

        return $value;
    }

    public function getSearchFieldType()
    {
        return 'string';
    }

    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return new opal\schema\Primitive_Set($this, $this->_options);
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setOptionStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getOptionStorageArray()
        );
    }
}
