<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\field;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

use df\axis;
use df\opal;

abstract class Base implements axis\schema\IField, \Serializable, Dumpable
{
    use opal\schema\TField;

    public static function factory(axis\schema\ISchema $schema, string $name, string $type, $args = null): axis\schema\IField
    {
        $parts = explode(':', $type);
        $superType = (string)array_shift($parts);
        $class = 'df\\axis\\schema\\field\\' . ucfirst($superType);

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Field type ' . $superType . ' could not be found'
            );
        }

        return new $class($schema, $type, $name, $args);
    }

    public function __construct(
        axis\schema\ISchema $schema,
        $type,
        $name,
        $args = null
    ) {
        $schema->getName();
        $this->_setName($name);
        $hasInit = false;

        if ($args === false) {
            return;
        }

        $parts = explode(':', $type, 2);
        $superType = array_shift($parts);

        if (!is_array($args)) {
            $args = [];
        }

        if ($subType = array_shift($parts)) {
            $method = '_initAs' . ucfirst($subType);

            if (method_exists($this, $method)) {
                $hasInit = true;
                $this->{$method}(...$args);
            } else {
                throw Exceptional::NotFound(
                    'Field type ' . $superType . ' does not support sub type ' . $subType
                );
            }
        }

        if (!$hasInit && method_exists($this, '_init')) {
            $this->_init(...$args);
        }
    }


    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        $output = clone $this;
        $output->_defaultValue = null;
        $output->_isNullable = false;

        if ($output instanceof opal\schema\IAutoIncrementableField) {
            $output->shouldAutoIncrement(false);
        }

        if ($output instanceof opal\schema\IAutoGeneratorField) {
            $output->shouldAutoGenerate(false);
        }

        return $output;
    }


    // Serialize
    public function serialize()
    {
        return json_encode($this->__serialize());
    }

    public function __serialize(): array
    {
        return $this->toStorageArray();
    }

    public function unserialize(string $data): void
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

    public function __unserialize(array $data): void
    {
        $this->_name = $data['nam'];
        $this->_importStorageArray($data);
    }


    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (isset($row[$key])) {
            return $row[$key];
        } else {
            return $this->_defaultValue;
        }
    }

    public function deflateValue($value)
    {
        return $value;
    }

    public function sanitizeClauseValue($value)
    {
        return $this->sanitizeValue($value);
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        return $value;
    }

    public function normalizeSavedValue($value, opal\record\IRecord $forRecord = null)
    {
        return $value;
    }

    public function generateInsertValue(array $row)
    {
        if ($this->_defaultValue !== null) {
            return $this->_defaultValue;
        } elseif ($this->isNullable()) {
            return null;
        } else {
            return $this->getNominalValue();
        }
    }

    public function getNominalValue()
    {
        return '';
    }

    public function compareValues($value1, $value2)
    {
        return $value1 == $value2;
    }

    public function getSearchFieldType()
    {
        return null;
    }

    public function getOrderableValue($outputValue)
    {
        return $outputValue;
    }

    public function canReturnNull()
    {
        return $this->isNullable();
    }


    // Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return $this;
    }

    public function validate(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return $this;
    }

    // Primitives
    public function getReplacedPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        $oldName = $schema->getOriginalFieldNameFor($this->_name);
        $replacedField = $schema->getReplacedField($oldName);

        if (!$replacedField) {
            return null;
        }

        return $replacedField->toPrimitive($unit, $schema);
    }


    // Ext. serialize
    public static function fromStorageArray(axis\schema\ISchema $schema, array $data)
    {
        $output = self::factory($schema, $data['nam'], $data['typ'], false);

        if ($output instanceof self) {
            $output->_importStorageArray($data);
        }

        return $output;
    }

    public function toStorageArray()
    {
        return $this->_getBaseStorageArray();
    }

    protected function _setBaseStorageArray(array $data)
    {
        $this->_setGenericStorageArray($data);
    }

    protected function _getBaseStorageArray()
    {
        return $this->_getGenericStorageArray();
    }

    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
    }


    public function getFieldTypeDisplayName()
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function getFieldSchemaString()
    {
        $type = $this->getFieldTypeDisplayName();
        $output = $this->_name . ' ' . $type;

        $args = [];

        if ($this instanceof opal\schema\ILengthRestrictedField
        && (null !== ($length = $this->getLength()))) {
            $args[] = $length;
        }

        if ($this instanceof opal\schema\IFloatingPointNumericField
        && (null !== ($precision = $this->getPrecision()))) {
            $args[] = $precision;
            $args[] = $this->getScale();
        }

        if ($this instanceof opal\schema\IBitSizeRestrictedField
        && (null !== ($size = $this->getBitSize()))) {
            $args[] = $size . ' bits';
        }

        if ($this instanceof opal\schema\IByteSizeRestrictedField
        && (null !== ($size = $this->getByteSize()))) {
            $args[] = $size . ' bytes';
        }

        if ($this instanceof opal\schema\ILargeByteSizeRestrictedField
        && (null !== ($size = $this->getExponentSize()))) {
            $args[] = '2 ^ ' . $size . ' bytes';
        }

        if (!empty($args)) {
            $output .= '(' . implode(', ', $args) . ')';
        }

        if ($this->_isNullable) {
            $output .= ' NULL';
        }

        if ($this instanceof opal\schema\IAutoTimestampField && $this->shouldTimestampAsDefault()) {
            $output .= ' DEFAULT now';
        } elseif ($this->_defaultValue !== null) {
            $output .= ' DEFAULT \'' . $this->_defaultValue . '\'';
        }

        if ($this instanceof opal\schema\IAutoTimestampField && $this->shouldTimestampOnUpdate()) {
            $output .= ' TIMESTAMP_ON_UPDATE';
        }

        if ($this instanceof opal\schema\ICharacterSetAwareField && (null !== ($characterSet = $this->getCharacterSet()))) {
            $output .= ' CHARSET ' . $characterSet;
        }

        return $output;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'definition' => $this->getFieldSchemaString();
    }
}
