<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\collection;

use df\core;

interface ICollection extends \Countable, core\IArrayProvider
{
    public function import(...$input);
    public function isEmpty(): bool;
    public function clear();
    public function extract();
    public function extractList(int $count): array;
}


trait TExtractList
{
    public function extractList(int $count): array
    {
        $output = [];

        for ($i = 0; $i < (int)$count; $i++) {
            $output[] = $this->extract();
        }

        return $output;
    }
}

trait TValueMapArrayAccess
{
    public function offsetSet(
        mixed $index,
        mixed $value
    ): void {
        $this->set($index, $value);
    }

    public function offsetGet(mixed $index): mixed
    {
        return $this->get($index);
    }

    public function offsetExists(mixed $index): bool
    {
        return $this->has($index);
    }

    public function offsetUnset(mixed $index): void
    {
        $this->remove($index);
    }
}


// Sortable
interface ISortable
{
    public function sortByValue($flags = \SORT_REGULAR);
    public function reverseSortByValue($flags = \SORT_REGULAR);
    public function sortByKey($flags = \SORT_REGULAR);
    public function reverseSortByKey($flags = \SORT_REGULAR);
    public function reverse();
    public function move($key, $index);
}

// Movable
interface IMovable
{
    public function move($key, $index);
}

trait TNaiveIndexedMovable
{
    public function move($key, $index)
    {
        $value = $this->get($key);
        return $this->remove($key)->put($key + $index, $value);
    }
}



// Seekable
interface ISeekable
{
    public function getCurrent();
    public function getFirst();
    public function getNext();
    public function getPrev();
    public function getLast();

    public function seekFirst();
    public function seekNext();
    public function seekPrev();
    public function seekLast();
    public function hasSeekEnded();
    public function getSeekPosition();
}



// Sliceable
interface ISliceable
{
    public function slice(int $offset, int $length = null): array;
    public function getSlice(int $offset, int $length = null): array;
    public function removeSlice(int $offset, int $length = null);
    public function keepSlice(int $offset, int $length = null);
}


// Extricate
interface IExtricable
{
    public function extricate(string ...$keys): array;
}

trait TExtricable
{
    public function extricate(string ...$keys): array
    {
        $output = [];

        foreach ($keys as $key) {
            $output[$key] = $this->get($key);
        }

        return $output;
    }
}


// Paging
interface IPageable
{
    public function setPaginator(?IPaginator $paginator);
    public function getPaginator(): ?IPaginator;
}

interface IPaginator extends core\IArrayProvider
{
    public function getLimit(): int;
    public function getOffset(): int;
    public function getPage(): int;
    public function setTotal(?int $total);
    public function countTotal(): ?int;
    public function countTotalPages(): ?int;
    public function getKeyMap(): array;
}

trait TPaginator
{
    protected $_limit = 30;
    protected $_offset = 0;
    protected $_total = null;

    protected $_keyMap = [
        'limit' => 'lm',
        'page' => 'pg',
        'offset' => 'of',
        'order' => 'od'
    ];

    public function getLimit(): int
    {
        return $this->_limit;
    }

    public function getOffset(): int
    {
        return $this->_offset;
    }

    public function getPage(): int
    {
        if (!$this->_limit) {
            $output = 1;
        } else {
            $output = ($this->_offset / $this->_limit) + 1;
        }

        $total = $this->countTotal();

        if ($total !== null) {
            if (!$this->_limit) {
                $test = 1;
            } else {
                $test = ceil($total / $this->_limit);
            }

            if ($test < $output) {
                $output = $test;
            }
        }

        return (int)$output;
    }

    public function getKeyMap(): array
    {
        return $this->_keyMap;
    }

    public function countTotal(): ?int
    {
        return $this->_total;
    }

    public function countTotalPages(): ?int
    {
        if (null === ($total = $this->countTotal())) {
            return null;
        }

        if (!$this->_limit) {
            return 1;
        } else {
            return (int)ceil($total / $this->getLimit());
        }
    }

    public function toArray(): array
    {
        return [
            'limit' => $this->_limit,
            'offset' => $this->_offset,
            'page' => $this->getPage(),
            'total' => $this->countTotal(),
            'totalPages' => $this->countTotalPages(),
            'keyMap' => $this->_keyMap
        ];
    }
}

interface IOrderablePaginator extends IPaginator
{
    public function getOrderDirectives();
    public function getOrderableFieldNames();
}


interface IInsertableCollection extends ICollection
{
    public function insert(...$value);
}

// Access to values by iteration only
interface IStreamCollection extends ICollection
{
    public function getCurrent();
}

interface ISiftingCollection extends IStreamCollection
{
}

interface IShiftableCollection extends ICollection, IInsertableCollection
{
    public function pop();
    public function push(...$values);
    public function shift();
    public function unshift(...$values);
}

interface IRandomAccessCollection extends IShiftableCollection, IMovable, core\IValueMap, \ArrayAccess
{
}



// Integer indexes only
interface IIndexedCollection extends IRandomAccessCollection, ISeekable, ISliceable
{
    public function put($index, $value);
    public function getIndex($value);
}

interface ISequentialCollection extends ICollection, IInsertableCollection
{
}

// Strict associative indexes
interface IMappedCollection extends ICollection, core\IValueMap, IExtricable, \ArrayAccess
{
}

// Object access returns container objects, otherwise, same behaviour as mapped
interface IMappedContainerCollection extends IMappedCollection
{
    public function __set($key, $value);
    public function __get($key);
    public function __isset($key);
    public function __unset($key): void;
}





interface IQueue extends ICollection
{
}
interface IIndexedQueue extends IQueue, IIndexedCollection
{
}
interface ISequentialQueue extends IQueue, ISequentialCollection
{
}

interface IPriorityQueue extends IQueue, ISiftingCollection
{
    public function insert($value, $priority);
    public function getCurrentPriority();
    public function getPriorityList();
}


interface IStack extends IIndexedCollection
{
}

interface IMap extends IMappedCollection, ISeekable, ISortable, IMovable
{
}
interface IHeap extends ISiftingCollection, IInsertableCollection
{
}


interface ISet extends ICollection
{
    public function add(...$values);
    public function has(...$values);
    public function remove(...$values);
    public function replace($current, $new);
}



interface ITree extends
    IRandomAccessCollection,
    IMappedContainerCollection,
    \IteratorAggregate,
    core\IUserValueContainer,
    core\IStringProvider
{
    public function importTree(ITree $child);
    public function merge(ITree $child);
    public function getNestedChild($parts, string $separator = '.');
    public function contains($value, $includeChildren = false);
    public function toArrayDelimitedString($setDelimiter = '&', $valueDelimiter = '=');
    public function toArrayDelimitedSet($prefix = null);
    public function toUrlEncodedArrayDelimitedSet($prefix = null);
    public function hasKey(...$keys);
    public function getKeys();
    public function clearKeys();
    public function removeEmpty();
    public function getChildren();
    public function hasAnyValue(array $checkKeys = null);
    public function replace($key, ITree $node);
}

interface IInputTree extends ITree, IErrorContainer
{
    public function toArrayDelimitedErrorSet($prefix = null);
}



interface IHeaderMap extends IMappedCollection, core\IStringProvider, \Iterator
{
    public function append($key, $value);
    public function hasValue($key, $value): bool;

    public function setBase($key, $value);
    public function getBase($key, $default = null);
    public function setDelimited($key, $base, array $values);
    public function getDelimited($key): ITree;
    public function setDelimitedValues($key, array $values);
    public function getDelimitedValues($key): array;
    public function setDelimitedValue($key, $name, $keyValue);
    public function getDelimitedValue($key, $name, $default = null);
    public function hasDelimitedValue($key, $name);
    public static function normalizeKey($key);
    public function getLines(array $skipKeys = null);
}

interface IHeaderMapProvider
{
    public function getHeaders();
    public function setHeaders(IHeaderMap $headers);
    public function hasHeaders();
    public function prepareHeaders();
    public function getHeaderString(array $skipKeys = null);
}


trait THeaderMapProvider
{
    protected $_headers;

    public function getHeaders()
    {
        if (!$this->_headers) {
            $this->_headers = new core\collection\HeaderMap();
        }

        return $this->_headers;
    }

    public function setHeaders(core\collection\IHeaderMap $headers)
    {
        $this->_headers = $headers;
        return $this;
    }

    public function prepareHeaders()
    {
        return $this;
    }

    public function hasHeaders()
    {
        return $this->_headers && !$this->_headers->isEmpty();
    }

    public function getHeaderString(array $skipKeys = null)
    {
        $this->prepareHeaders();

        if ($this->_headers) {
            return $this->_headers->toString($skipKeys);
        }

        return '';
    }
}


// Error Container
interface IErrorContainer
{
    public function isValid(): bool;
    public function countErrors(): int;
    public function setErrors(array $errors);
    public function addErrors(array $errors);
    public function addError($code, $message);
    public function getErrors();
    public function getError($code);
    public function hasErrors();
    public function hasError($code);
    public function clearErrors();
    public function clearError($code);
}

trait TErrorContainer
{
    protected $_errors = [];

    public function isValid(): bool
    {
        return $this->hasErrors();
    }

    public function countErrors(): int
    {
        return count($this->_errors);
    }

    public function setErrors(array $errors)
    {
        $this->_errors = [];
        return $this->addErrors($errors);
    }

    public function addErrors(array $errors)
    {
        foreach ($errors as $code => $message) {
            $this->addError($code, $message);
        }

        return $this;
    }

    public function addError($code, $message)
    {
        $this->_errors[$code] = $message;
        return $this;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getError($code)
    {
        if (isset($this->_errors[$code])) {
            return $this->_errors[$code];
        }

        return null;
    }

    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    public function hasError($code)
    {
        return isset($this->_errors[$code]);
    }

    public function clearErrors()
    {
        $this->_errors = [];
        return $this;
    }

    public function clearError($code)
    {
        unset($this->_errors[$code]);
        return $this;
    }
}


// Attribute container
interface IAttributeContainer
{
    public function setAttributes(array $attributes);
    public function addAttributes(array $attributes);
    public function getAttributes();
    public function setAttribute($key, $value);
    public function getAttribute($key, $default = null);
    public function removeAttribute($key);
    public function hasAttribute($key);
    public function countAttributes();
}

trait TAttributeContainer
{
    protected $_attributes = [];

    public function setAttributes(array $attributes)
    {
        $this->_attributes = [];
        return $this->addAttributes($attributes);
    }

    public function addAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    public function setAttribute($key, $value)
    {
        $this->_attributes[$key] = $value;
        return $this;
    }

    public function getAttribute($key, $default = null)
    {
        if (isset($this->_attributes[$key])) {
            return $this->_attributes[$key];
        }

        return $default;
    }

    public function removeAttribute($key)
    {
        unset($this->_attributes[$key]);
        return $this;
    }

    public function hasAttribute($key)
    {
        return isset($this->_attributes[$key]);
    }

    public function countAttributes()
    {
        return count($this->_attributes);
    }
}

trait TAttributeContainerArrayAccessProxy
{
    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->setAttribute($key, $value);
    }

    public function offsetGet(mixed $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->hasAttribute($key);
    }

    public function offsetUnset(mixed $key): void
    {
        $this->removeAttribute($key);
    }
}

trait TArrayAccessedAttributeContainer
{
    use TAttributeContainer;
    use TAttributeContainerArrayAccessProxy;
}



// Util
interface IUtil
{
    public static function flatten($array, bool $unique = true, bool $removeNull = false);
    public static function leaves($data, bool $removeNull = false);
    public static function isIterable($collection);
    public static function ensureIterable($collection);

    public static function normalizeEnumValue($value, array $map, $defaultValue = null);
    public static function exportArray(array $values, $level = 1);
}
