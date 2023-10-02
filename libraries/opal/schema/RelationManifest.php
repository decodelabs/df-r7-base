<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\schema;

use ArrayIterator;

use DecodeLabs\Glitch\Dumpable;

use df\opal;

class RelationManifest implements IRelationManifest, Dumpable
{
    protected $_fields = [];
    protected $_primitives = [];

    public function __construct(opal\schema\IIndex $index)
    {
        foreach ($index->getFields() as $name => $field) {
            if ($field instanceof opal\schema\ITargetPrimaryFieldAwareRelationField) {
                $this->_fields[$name] = $field->getTargetRelationManifest();

                foreach ($this->_fields[$name]->getPrimitives($name) as $subField => $processor) {
                    $this->_primitives[$subField] = $processor;
                }
            } elseif ($field instanceof opal\schema\IMultiPrimitiveField) {
                $this->_fields[$name] = $field->getPrimitiveFieldNames();

                // TODO: get processors ??

                foreach ($this->_fields[$name] as $subField) {
                    $this->_primitives[$subField] = null;
                }
            } else {
                $this->_fields[$name] = $name;
                $this->_primitives[$name] = $field instanceof opal\query\IFieldValueProcessor ? $field : null;
            }
        }
    }

    public function getPrimitiveFieldNames($prefix = null)
    {
        if ($prefix !== null) {
            $output = [];
            $prefix = rtrim((string)$prefix, '_') . '_';

            foreach ($this->_primitives as $name => $processor) {
                $output[] = $prefix . $name;
            }

            return $output;
        }

        return array_keys($this->_primitives);
    }

    public function getPrimitives($prefix = null)
    {
        if ($prefix !== null) {
            $output = [];
            $prefix = rtrim((string)$prefix, '_') . '_';

            foreach ($this->_primitives as $name => $processor) {
                $output[$prefix . $name] = $processor;
            }

            return $output;
        }

        return $this->_primitives;
    }

    public function isSingleField()
    {
        return count($this->_primitives) == 1;
    }

    public function getSingleFieldName()
    {
        reset($this->_primitives);
        return key($this->_primitives);
    }

    public function validateValue($value)
    {
        $primitiveFields = $this->getPrimitiveFieldNames();
        $fieldCount = count($primitiveFields);

        if (is_scalar($value)) {
            return $fieldCount == 1;
        }

        return true;
    }

    public function extractFromRow($key, array $row)
    {
        $returnVal = count($this->_primitives) == 1;
        $output = [];

        foreach ($this->_primitives as $name => $processor) {
            if ($returnVal && isset($row[$key])) {
                $value = $row[$key];
            } else {
                $testKey = $key . '_' . $name;

                if (isset($row[$testKey])) {
                    $value = $row[$testKey];
                } else {
                    $value = null;
                }
            }

            if ($value instanceof opal\record\IPrimaryKeySetProvider
            && !$value instanceof opal\record\IDataProvider) {
                $value = $value->getPrimaryKeySet();
            }

            if ($processor && !$value instanceof opal\record\IDataProvider) {
                $value = $processor->sanitizeValue($value);
            }

            if ($returnVal) {
                return $value;
            }

            $output[$name] = $value;
        }

        return $output;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->_fields);
    }

    public function toArray(): array
    {
        return $this->_fields;
    }


    public function toPrimaryKeySet($value = null)
    {
        $values = [];

        foreach ($this->_fields as $name => $field) {
            if ($field instanceof IRelationManifest) {
                $values[$name] = $field->toPrimaryKeySet();
            } elseif (is_array($field)) {
                foreach ($field as $subField) {
                    $values[$subField] = null;
                }
            } else {
                $values[$name] = null;
            }
        }

        $output = new opal\record\PrimaryKeySet(array_keys($values), $values);

        if ($value !== null) {
            $output->updateWith($value);
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'values' => $this->_fields;
    }
}
