<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;

use DecodeLabs\Exceptional;

class AutoId extends Base implements
    opal\schema\IByteSizeRestrictedField,
    opal\schema\IAutoGeneratorField,
    axis\schema\IAutoPrimaryField,
    opal\schema\IAutoIncrementableField
{
    use opal\schema\TField_ByteSizeRestricted;
    use axis\schema\TAutoPrimaryField;

    protected function _init($size=null)
    {
        $this->setByteSize($size);
    }


    // Auto inc
    public function shouldAutoGenerate(bool $flag=null)
    {
        if ($flag !== null) {
            if (!$flag) {
                throw Exceptional::Logic(
                    'AutoId field must auto increment'
                );
            }

            return $this;
        }

        return true;
    }

    public function shouldAutoIncrement(bool $flag=null)
    {
        if ($flag !== null) {
            if (!$flag) {
                throw Exceptional::Logic(
                    'AutoId field must auto increment'
                );
            }

            return $this;
        }

        return true;
    }

    public function isSigned(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                throw Exceptional::Logic(
                    'AutoId field must be unsigned'
                );
            }

            return $this;
        }

        return false;
    }

    public function isUnsigned(bool $flag=null)
    {
        if ($flag !== null) {
            if (!$flag) {
                throw Exceptional::Logic(
                    'AutoId field must be unsigned'
                );
            }

            return $this;
        }

        return true;
    }

    public function shouldZerofill(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                throw Exceptional::Logic(
                    'AutoId field must not zero-fill'
                );
            }

            return $this;
        }

        return false;
    }



    // Values
    public function deflateValue($value)
    {
        if (empty($value)) {
            $value = null;
        }

        return $value;
    }

    public function getSearchFieldType()
    {
        return 'integer';
    }

    // Primitive
    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        $output = new Number($schema, 'Number:Integer', $this->_name, [$this->_byteSize]);
        $output->isUnsigned(true);

        return $output;
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        $output = new opal\schema\Primitive_Integer($this, $this->_byteSize);
        $output->isUnsigned(true);
        $output->shouldAutoIncrement(true);

        return $output;
    }


    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setByteSizeRestrictedStorageArray($data);
        $this->_setAutoPrimaryStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getByteSizeRestrictedStorageArray(),
            $this->_getAutoPrimaryStorageArray()
        );
    }
}
