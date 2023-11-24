<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\record;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;
use Stringable;

class PrimaryKeySet implements IPrimaryKeySet, Dumpable
{
    public const COMBINE_SEPARATOR = '+';

    protected $_keys = [];

    public static function fromEntityId($id)
    {
        if (substr($id, 0, 7) != 'keySet?') {
            throw Exceptional::InvalidArgument(
                'Invalid entity id: ' . $id
            );
        }

        $id = substr($id, 7);
        $tree = core\collection\Tree::fromArrayDelimitedString($id);
        $values = [];

        foreach ($tree as $key => $value) {
            $value = substr($value->getValue(), 1, -1);

            if (substr($value, 0, 7) == 'keySet?') {
                $value = self::fromEntityId($value);
            }

            $values[$key] = $value;
        }

        return new self(array_keys($values), $values);
    }

    public function __construct(array $fields, $values = [])
    {
        $this->_keys = array_fill_keys($fields, null);
        $this->updateWith($values);
    }

    public function __clone()
    {
        foreach ($this->_keys as $key => $value) {
            if ($value instanceof self) {
                $this->_keys[$key] = clone $value;
            }
        }
    }

    public function getKeys(): array
    {
        return $this->_keys;
    }

    public function __toString(): string
    {
        foreach ($this->_keys as $key => $value) {
            return (string)$value;
        }

        return '';
    }

    public function toArray(): array
    {
        $output = [];

        foreach ($this->_keys as $key => $value) {
            if ($value instanceof self) {
                foreach ($value->toArray() as $subKey => $subValue) {
                    $output[$key . '_' . $subKey] = $subValue;
                }
            } else {
                $output[$key] = $value;
            }
        }

        return $output;
    }

    public function getKeyMap($fieldName): array
    {
        $output = [];

        foreach ($this->_keys as $key => $value) {
            if ($value instanceof self) {
                foreach ($value->toArray() as $subKey => $subValue) {
                    $output[$key . '_' . $subKey] = $fieldName . '_' . $key . '_' . $subKey;
                }
            } else {
                $output[$key] = $fieldName . '_' . $key;
            }
        }

        return $output;
    }

    public function getIntrinsicFieldMap($fieldName = null): array
    {
        if ($fieldName === null) {
            return $this->toArray();
        }

        $output = [];

        foreach ($this->_keys as $key => $value) {
            if ($value instanceof self) {
                foreach ($value->toArray() as $subKey => $subValue) {
                    $output[$fieldName . '_' . $key . '_' . $subKey] = $subValue;
                }
            } else {
                $output[$fieldName . '_' . $key] = $value;
            }
        }

        return $output;
    }

    public function updateWith($values)
    {
        if ($values instanceof IPrimaryKeySetProvider) {
            $values = $values->getPrimaryKeySet();
        }

        if ($values instanceof self) {
            $values = $values->_keys;
        }

        if (!is_array($values)) {
            if ($values === null || count($this->_keys) == 1) {
                $values = array_fill_keys(array_keys($this->_keys), $values);
            } else {
                throw Exceptional::InvalidArgument(
                    'Primary key set values do not map to keys'
                );
            }
        }

        foreach ($this->_keys as $field => $origValue) {
            if ($origValue instanceof self) {
                $inner = null;

                foreach ($values as $key => $value) {
                    if ($key == $field) {
                        $inner = $value;
                        break;
                    } elseif (0 === strpos($key, $field . '_')) {
                        $inner = [];
                        $parts = explode('_', $key, 2);
                        $inner[array_pop($parts)] = $value;
                    }
                }

                $origValue->updateWith($inner);
                continue;
            }

            $value = null;

            if (isset($values[$field])) {
                $value = $values[$field];
            } elseif (false !== strpos($field, '_')) {
                $parts = explode('_', $field, 2);

                if (isset($values[$parts[0]])) {
                    $value = $values[$parts[0]][$parts[1]];
                }
            }

            if ($value instanceof IPrimaryKeySetProvider) {
                $value = $value->getPrimaryKeySet();
            }

            if ($this->_keys[$field] instanceof self) {
                $this->_keys[$field]->updateWith($value);
            } else {
                $this->_keys[$field] = $value;
            }
        }

        return $this;
    }

    public function countFields(): int
    {
        return count($this->_keys);
    }

    public function getFieldNames(): array
    {
        return array_keys($this->toArray());
    }

    public function isNull(): bool
    {
        foreach ($this->_keys as $value) {
            if ($value === null) {
                return true;
            }

            if ($value instanceof IPrimaryKeySet && $value->isNull()) {
                return true;
            }
        }

        return false;
    }

    public function getCombinedId(): string
    {
        $strings = [];

        foreach ($this->_keys as $key) {
            if ($key instanceof IPrimaryKeySetProvider) {
                $key = $key->getPrimaryKeySet();
            }

            if ($key instanceof self) {
                $key = '[' . $key->getCombinedId() . ']';
            }

            $strings[] = (string)$key;
        }

        return implode(self::COMBINE_SEPARATOR, $strings);
    }

    public function getEntityId(): string
    {
        $returnFirst = false;

        if (count($this->_keys) == 1) {
            $returnFirst = true;
        }

        $output = new core\collection\Tree();

        foreach ($this->_keys as $key => $value) {
            if ($value instanceof IPrimaryKeySetProvider) {
                $returnFirst = false;
                $value = $value->getPrimaryKeySet();
            }

            if ($value instanceof self) {
                $returnFirst = false;
                $value = '[' . $value->getEntityId() . ']';
            }

            if ($returnFirst) {
                return (string)$value;
            }

            $output->{$key} = (string)$value;
        }

        return 'keySet?' . $output->toArrayDelimitedString();
    }

    public function getValue()
    {
        if (count($this->_keys) == 1) {
            return $this->getFirstKeyValue();
        }

        return $this->_keys;
    }

    public function getFirstKeyValue()
    {
        foreach ($this->_keys as $value) {
            if ($value instanceof IPrimaryKeySet) {
                $value = $value->getValue();
            }

            return $value;
        }
    }

    public function getRawValue()
    {
        if (count($this->_keys) > 1) {
            return $this;
        }

        $output = $this->getFirstKeyValue();

        if ($output instanceof self) {
            $output = $output->getRawValue();
        }

        return $output;
    }

    public function duplicateWith($values)
    {
        $output = clone $this;
        $output->updateWith($values);
        return $output;
    }

    public function eq(IPrimaryKeySet $keySet)
    {
        $testKeys = $keySet->getKeys();

        foreach ($this->_keys as $key => $value) {
            if (!array_key_exists($key, $testKeys)) {
                return false;
            }

            if ($testKeys[$key] === $value) {
                continue;
            }

            if (
                (
                    $value instanceof Stringable ||
                    is_string($value)
                ) &&
                (
                    $testKeys[$key] instanceof Stringable ||
                    is_string($testKeys[$key])
                )
            ) {
                if ((string)$value === (string)$testKeys[$key]) {
                    continue;
                }
            }

            return false;
        }

        return true;
    }


    // Array access
    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->_keys[$key] = $value;
    }

    public function offsetGet(mixed $key): mixed
    {
        if (isset($this->_keys[$key])) {
            return $this->_keys[$key];
        }

        return null;
    }

    public function offsetExists(mixed $key): bool
    {
        return isset($this->_keys[$key]);
    }

    public function offsetUnset(mixed $key): void
    {
        unset($this->_keys[$key]);
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'values' => $this->_keys;
    }
}
