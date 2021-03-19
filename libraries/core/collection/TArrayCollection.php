<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

use DecodeLabs\Exceptional;

trait TArrayCollection
{
    use TExtractList;

    protected $_collection = [];

    public function isEmpty(): bool
    {
        return empty($this->_collection);
    }

    public function clear()
    {
        $this->_collection = [];
        return $this;
    }

    public function extract()
    {
        return array_shift($this->_collection);
    }

    public function toArray(): array
    {
        return $this->_collection;
    }

    public function count()
    {
        return count($this->_collection);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->_collection);
    }

    protected function _normalizeValue($value, $key=null)
    {
        return $value;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'values' => $this->_collection;
    }
}

trait TArrayCollection_Constructor
{
    public function __construct(...$input)
    {
        if (!empty($input)) {
            $this->import(...$input);
        }
    }
}



// Sortable
trait TArrayCollection_Sortable
{
    public function sortByKey($flags=\SORT_REGULAR)
    {
        ksort($this->_collection, $flags);
        return $this;
    }

    public function reverseSortByKey($flags=\SORT_REGULAR)
    {
        krsort($this->_collection, $flags);
        return $this;
    }

    public function reverse()
    {
        $this->_collection = array_reverse($this->_collection);
        return $this;
    }
}

trait TArrayCollection_ScalarSortable
{
    use TArrayCollection_Sortable;

    public function sortByValue($flags=\SORT_REGULAR)
    {
        asort($this->_collection, $flags);
        return $this;
    }

    public function reverseSortByValue($flags=\SORT_REGULAR)
    {
        arsort($this->_collection, $flags);
        return $this;
    }
}

trait TArrayCollection_ValueContainerSortable
{
    use TArrayCollection_Sortable;

    public function sortByValue($flags=\SORT_REGULAR)
    {
        uasort($this->_collection, function (core\IValueContainer $a, core\IValueContainer $b) {
            $a = $a->getValue();
            $b = $b->getValue();
            return $a <=> $b;
        });
    }

    public function reverseSortByValue($flags=\SORT_REGULAR)
    {
        uasort($this->_collection, function (core\IValueContainer $b, core\IValueContainer $a) {
            $a = $a->getValue();
            $b = $b->getValue();
            return $a <=> $b;
        });
    }
}

trait TArrayCollection_IndexedMovable
{
    public function move($key, $index)
    {
        $key = (int)$key;
        $index = (int)$index;
        $count = count($this->_collection);

        if ($count <= 1) {
            return $this;
        }

        if ($key < 0) {
            $key += $count;

            if ($key < 0) {
                throw Exceptional::OutOfBounds(
                    'Trying to move a negative index outside of current bounds'
                );
            }
        }

        if ($index == 0 || !array_key_exists($key, $this->_collection)) {
            return $this;
        }

        $temp = [];

        if ($index < 0) {
            foreach ($this->_collection as $currentKey => $value) {
                if ($currentKey == $key) {
                    $buffer = array_slice($temp, $index);
                    $temp = array_slice($temp, 0, $index);
                    $temp[] = $value;
                    $temp = array_merge($temp, $buffer);
                    continue;
                }

                $temp[] = $value;
            }
        } else {
            $found = false;
            $keyValue = $this->_collection[$key];

            foreach ($this->_collection as $currentKey => $value) {
                if ($currentKey == $key) {
                    $found = true;
                    $index--;
                    continue;
                }

                if ($found) {
                    $index--;
                }

                $temp[] = $value;

                if ($index == 0) {
                    $temp[] = $keyValue;
                    $index = 0;
                }
            }

            if ($index > 0) {
                $temp[] = $keyValue;
            }
        }

        $this->_collection = $temp;
        return $this;
    }
}


trait TArrayCollection_MappedMovable
{
    public function move($key, $index)
    {
        $index = (int)$index;
        $count = count($this->_collection);

        if ($count <= 1) {
            return $this;
        }

        if (!array_key_exists($key, $this->_collection)) {
            return $this;
        }

        $temp = [];

        if ($index < 0) {
            foreach ($this->_collection as $currentKey => $value) {
                if ($currentKey == $key) {
                    $buffer = array_slice($temp, $index, null, true);
                    $temp = array_slice($temp, 0, $index, true);
                    $temp[$key] = $value;

                    foreach ($buffer as $bKey => $bValue) {
                        $temp[$bKey] = $bValue;
                    }

                    continue;
                }

                $temp[$currentKey] = $value;
            }
        } else {
            $keyValue = null;
            $i = 0;
            $found = $inserted = false;

            foreach ($this->_collection as $currentKey => $value) {
                if ($key == $currentKey) {
                    $found = true;
                    $keyValue = $value;
                    continue;
                }

                if ($i == $index) {
                    $temp[$key] = $keyValue;
                    $inserted = true;
                }

                $temp[$currentKey] = $value;

                if ($found) {
                    $i++;
                }
            }

            if (!$inserted) {
                $temp[$key] = $keyValue;
            }
        }

        $this->_collection = $temp;
        return $this;
    }
}


// Value map
trait TArrayCollection_AssociativeValueMap
{
    use core\TValueMap;
    use TValueMapArrayAccess;
    use TExtricable;

    public function import(...$input)
    {
        foreach ($input as $data) {
            if (core\collection\Util::isIterable($data)) {
                foreach ($data as $key => $value) {
                    $this->set($key, $value);
                }
            }
        }

        return $this;
    }

    public function set($key, $value)
    {
        $this->_collection[(string)$key] = $this->_normalizeValue($value, $key);
        return $this;
    }

    public function get($key, $default=null)
    {
        $key = (string)$key;

        if (array_key_exists($key, $this->_collection)) {
            return $this->_collection[$key];
        }

        return $default;
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists((string)$key, $this->_collection)) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            unset($this->_collection[(string)$key]);
        }

        return $this;
    }
}



trait TArrayCollection_IndexedValueMap
{
    use core\TValueMap;
    use TValueMapArrayAccess;

    public function import(...$input)
    {
        foreach ($input as $value) {
            $this->_collection[] = $this->_normalizeValue($value);
        }

        return $this;
    }

    public function insert(...$values)
    {
        return $this->import(...$values);
    }

    public function set($index, $value)
    {
        $count = count($this->_collection);

        if ($index === null) {
            $index = $count;
        }

        $index = (int)$index;

        if ($index < 0) {
            $index += $count;

            if ($count == 0 && $index == -1) {
                $index = 0;
            }

            if ($index < 0) {
                throw Exceptional::OutOfBounds(
                    'Trying to set a negative index outside of current bounds'
                );
            }
        }

        if ($index > $count) {
            $index = $count;
        }

        $this->_collection[$index] = $this->_normalizeValue($value, $index);
        return $this;
    }

    public function put($index, $value)
    {
        $count = count($this->_collection);
        $index = (int)$index;

        if ($index < 0) {
            $index += $count;

            if ($count == 0 && $index == -1) {
                $index = 0;
            }

            if ($index < 0) {
                throw Exceptional::OutOfBounds(
                    'Trying to set a negative index outside of current bounds'
                );
            }
        }

        $addVals = null;

        if ($index < $count) {
            $addVals = array_splice($this->_collection, $index);
            $count = $index;
        }

        $this->_collection[] = $this->_normalizeValue($value, $index);

        if ($addVals !== null) {
            $this->_collection = array_merge($this->_collection, $addVals);
        }

        return $this;
    }

    public function get($index, $default=null)
    {
        $index = (int)$index;

        if ($index < 0) {
            $index += count($this->_collection);

            if ($index < 0) {
                return $default;
            }
        }

        if (array_key_exists($index, $this->_collection)) {
            return $this->_collection[$index];
        }

        return $default;
    }

    public function has(...$indexes)
    {
        foreach ($indexes as $index) {
            $index = (int)$index;

            if ($index < 0) {
                $index += count($this->_collection);

                if ($index < 0) {
                    continue;
                }
            }

            if (array_key_exists($index, $this->_collection)) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$indexes)
    {
        foreach ($indexes as $index) {
            $index = (int)$index;

            if ($index < 0) {
                $index += count($this->_collection);

                if ($index < 0) {
                    continue;
                }
            }

            unset($this->_collection[$index]);
        }

        $this->_collection = array_values($this->_collection);
        return $this;
    }

    public function getIndex($value)
    {
        $value = $this->_normalizeValue($value);

        if (false === ($output = array_search($value, $this->_collection))) {
            $output = null;
        }

        return $output;
    }
}


trait TArrayCollection_ProcessedIndexedValueMap
{
    use TArrayCollection_IndexedValueMap;

    public function set($index, $value)
    {
        $values = $this->_expandInput($value);

        if (!$valCount = count($values)) {
            return $this->remove($index);
        }

        $count = count($this->_collection);

        if ($index === null) {
            $index = $count;
        }

        $index = (int)$index;

        if ($index < 0) {
            $index += $count;

            if ($count == 0 && $index == -1) {
                $index = 0;
            }

            if ($index < 0) {
                throw Exceptional::OutOfBounds(
                    'Trying to set a negative index outside of current bounds'
                );
            }
        }

        if ($index > $count) {
            $index = $count;
        }

        while ($valCount > 0) {
            $this->_collection[$index] = $this->_normalizeValue(array_shift($values));
            $valCount--;
            $index++;
        }

        $this->_onInsert();
        return $this;
    }

    public function put($index, $value)
    {
        $values = $this->_expandInput($value);

        if (!$valCount = count($values)) {
            return $this;
        }

        $count = count($this->_collection);
        $index = (int)$index;

        if ($index < 0) {
            $index += $count;

            if ($count == 0 && $index == -1) {
                $index = 0;
            }

            if ($index < 0) {
                throw Exceptional::OutOfBounds(
                    'Trying to set a negative index outside of current bounds'
                );
            }
        }

        $addVals = null;

        if ($index < $count) {
            $addVals = array_splice($this->_collection, $index);
            $count = $index;
        }

        while ($valCount > 0) {
            $this->_collection[] = $this->_normalizeValue(array_shift($values));
            $valCount--;
        }

        if ($addVals !== null) {
            $this->_collection = array_merge($this->_collection, $addVals);
        }

        $this->_onInsert();
        return $this;
    }

    public function getIndex($value)
    {
        $value = $this->_normalizeValue($value);

        if (false === ($output = array_search($value, $this->_collection))) {
            $output = null;
        }

        return $output;
    }

    protected function _expandInput($input): array
    {
        return (array)$input;
    }

    protected function _onInsert()
    {
    }
}


trait TArrayCollection_UniqueSet
{
    public function add(...$values)
    {
        array_walk($values, function (&$value) {
            $value = $this->_normalizeValue($value);
        });

        $this->_collection = array_unique(array_merge(
            $this->_collection, $values
        ));

        return $this;
    }

    public function has(...$values)
    {
        foreach ($values as $value) {
            if (in_array($value, $this->_collection, true)) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$values)
    {
        $this->_collection = array_diff($this->_collection, $values);
        return $this;
    }

    public function replace($current, $new)
    {
        foreach ($this->_collection as $i => $setValue) {
            if ($setValue === $current) {
                $this->_collection[$i] = $this->_normalizeValue($new);
                break;
            }
        }

        return $this;
    }
}


// Seekable
trait TArrayCollection_Seekable
{
    public function getCurrent()
    {
        if (false === ($output = current($this->_collection))) {
            return null;
        }

        return $output;
    }

    public function getFirst()
    {
        return reset($this->_collection);
    }

    public function getNext()
    {
        return next($this->_collection);
    }

    public function getPrev()
    {
        return prev($this->_collection);
    }

    public function getLast()
    {
        return end($this->_collection);
    }

    public function seekFirst()
    {
        reset($this->_collection);
        return $this;
    }

    public function seekNext()
    {
        next($this->_collection);
        return $this;
    }

    public function seekPrev()
    {
        prev($this->_collection);
        return $this;
    }

    public function seekLast()
    {
        end($this->_collection);
        return $this;
    }

    public function hasSeekEnded()
    {
        return key($this->_collection) === null;
    }

    public function getSeekPosition()
    {
        return key($this->_collection);
    }
}


trait TArrayCollection_ReverseSeekable
{
    public function getCurrent()
    {
        if (false === ($output = current($this->_collection))) {
            return null;
        }

        return $output;
    }

    public function getFirst()
    {
        return end($this->_collection);
    }

    public function getNext()
    {
        return prev($this->_collection);
    }

    public function getPrev()
    {
        return next($this->_collection);
    }

    public function getLast()
    {
        return reset($this->_collection);
    }

    public function seekFirst()
    {
        end($this->_collection);
        return $this;
    }

    public function seekNext()
    {
        prev($this->_collection);
        return $this;
    }

    public function seekPrev()
    {
        next($this->_collection);
        return $this;
    }

    public function seekLast()
    {
        reset($this->_collection);
        return $this;
    }

    public function hasSeekEnded()
    {
        return key($this->_collection) === null;
    }

    public function getSeekPosition()
    {
        return key($this->_collection);
    }
}



// Sliceable
trait TArrayCollection_Sliceable
{
    public function slice(int $offset, int $length=null): array
    {
        if ($length === null) {
            return array_splice($this->_collection, $offset);
        } else {
            return array_splice($this->_collection, $offset, $length);
        }
    }

    public function getSlice(int $offset, int $length=null): array
    {
        return array_slice($this->_collection, $offset, $length);
    }

    public function removeSlice(int $offset, int $length=null)
    {
        $this->slice($offset, $length);
        return $this;
    }

    public function keepSlice(int $offset, int $length=null)
    {
        $this->_collection = $this->getSlice($offset, $length);
        return $this;
    }
}


// Shiftable
trait TArrayCollection_Shiftable
{
    public function pop()
    {
        return array_pop($this->_collection);
    }

    public function push(...$values)
    {
        array_walk($values, function (&$value, $key) {
            $value = $this->_normalizeValue($value, $key);
        });

        array_push($this->_collection, ...$values);
        return $this;
    }

    public function shift()
    {
        return array_shift($this->_collection);
    }

    public function unshift(...$values)
    {
        array_walk($values, function (&$value, $key) {
            $value = $this->_normalizeValue($value, $key);
        });

        array_unshift($this->_collection, ...$values);
        return $this;
    }
}

trait TArrayCollection_ProcessedShiftable
{
    public function pop()
    {
        return array_pop($this->_collection);
    }

    public function push(...$values)
    {
        $insert = [];
        $count = count($this->_collection);

        array_walk($values, function ($value, $i) use (&$insert) {
            if (!empty($expanded = $this->_expandInput($value))) {
                array_push($insert, ...array_values($expanded));
            }
        });

        array_walk($insert, function (&$value, $j) use ($count) {
            $value = $this->_normalizeValue($value, $count + $j);
        });

        if (!empty($insert)) {
            array_push($this->_collection, ...$insert);
        }

        $this->_onInsert();
        return $this;
    }

    public function shift()
    {
        return array_shift($this->_collection);
    }

    public function unshift(...$values)
    {
        $insert = [];

        array_walk($values, function ($value, $i) use (&$insert) {
            if (!empty($expanded = $this->_expandInput($value))) {
                array_push($insert, ...array_values($expanded));
            }
        });

        array_walk($insert, function (&$value, $j) {
            $value = $this->_normalizeValue($value, $j);
        });

        if (!empty($insert)) {
            array_unshift($this->_collection, ...$insert);
        }

        $this->_onInsert();
        return $this;
    }

    abstract protected function _expandInput($input): array;
    abstract protected function _onInsert();
}



// Full Implementations
trait TArrayCollection_Queue
{
    use TArrayCollection;
    use TArrayCollection_IndexedValueMap;
    use TArrayCollection_Seekable;
    use TArrayCollection_Sliceable;
    use TArrayCollection_Shiftable;
    use TArrayCollection_IndexedMovable;
}

trait TArrayCollection_Stack
{
    use TArrayCollection;
    use TArrayCollection_IndexedValueMap;
    use TArrayCollection_ReverseSeekable;
    use TArrayCollection_Sliceable;
    use TArrayCollection_Shiftable;
    use TArrayCollection_IndexedMovable;

    public function getIterator()
    {
        return new \ArrayIterator(array_reverse($this->_collection, true));
    }

    public function extract()
    {
        return $this->pop();
    }
}

trait TArrayCollection_Map
{
    use TArrayCollection;
    use TArrayCollection_ScalarSortable;
    use TArrayCollection_AssociativeValueMap;
    use TArrayCollection_Seekable;
    use TArrayCollection_MappedMovable;
}
