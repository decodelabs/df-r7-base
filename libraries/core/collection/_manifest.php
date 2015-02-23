<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

// Exceptions
interface IException {}
class OutOfBoundsException extends \OutOfBoundsException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ICollection extends \Countable, core\IArrayProvider {
    public function import($input);
    public function isEmpty();
    public function clear();
    public function extract();
    public function extractList($count);
}


trait TExtractList {
    
    public function extractList($count) {
        $output = [];
        
        for($i = 0; $i < (int)$count; $i++) {
            $output[] = $this->extract();
        }
        
        return $output;
    }
}


interface IAggregateIteratorCollection extends \IteratorAggregate {
    public function getReductiveIterator();
}

trait TValueMapArrayAccess {
        
    public function offsetSet($index, $value) {
        return $this->set($index, $value);
    }
    
    public function offsetGet($index) {
        return $this->get($index);
    }
    
    public function offsetExists($index) {
        return $this->has($index);
    }
    
    public function offsetUnset($index) {
        return $this->remove($index);
    }
}


// Sortable
interface ISortable {
    public function sortByValue($flags=\SORT_REGULAR);
    public function reverseSortByValue($flags=\SORT_REGULAR);
    public function sortByKey($flags=\SORT_REGULAR);
    public function reverseSortByKey($flags=\SORT_REGULAR);
    public function reverse();
    public function move($key, $index);
}

// Movable
interface IMovable {
    public function move($key, $index);
}

trait TNaiveIndexedMovable {

    public function move($key, $index) {
        $value = $this->get($key);
        return $this->remove($key)->put($key + $index, $value);
    }
}



// Seekable
interface ISeekable {
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
interface ISliceable {
    public function slice($offset, $length=null);
    public function getSlice($offset, $length=null);
    public function removeSlice($offset, $length=null);
    public function keepSlice($offset, $length=null);
}


// Paging
interface IPageable {
    public function setPaginator(IPaginator $paginator);
    public function getPaginator();
}

interface IPaginator extends core\IArrayProvider {
    public function getLimit();
    public function getOffset();
    public function getPage();
    public function setTotal($total);
    public function countTotal();
    public function countTotalPages();
    public function getKeyMap();
}

trait TPaginator {

    protected $_limit = 30;
    protected $_offset = 0;
    protected $_total = null;

    protected $_keyMap = [
        'limit' => 'lm',
        'page' => 'pg',
        'offset' => 'of',
        'order' => 'od'
    ];

    public function getLimit() {
        return $this->_limit;
    }

    public function getOffset() {
        return $this->_offset;
    }

    public function getPage() {
        $output = ($this->_offset / $this->_limit) + 1;
        $total = $this->countTotal();

        if($total !== null) {
            $test = ceil($total / $this->_limit);

            if($test < $output) {
                $output = $test;
            }
        }

        return (int)$output;
    }

    public function getKeyMap() {
        return $this->_keyMap;
    }

    public function countTotal() {
        return (int)$this->_total;
    }

    public function countTotalPages() {
        $total = $this->countTotal();
        return ceil($total / $this->getLimit());
    }

    public function toArray() {
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

interface IOrderablePaginator extends IPaginator {
    public function getOrderDirectives();
    public function getOrderableFieldNames();
}




// Access to values by iteration only
interface IStreamCollection extends ICollection {
    public function getCurrent();
}

interface ISiftingCollection extends IStreamCollection {}

interface IShiftableCollection extends ICollection {
    public function insert($value);
    public function pop();
    public function push($value);
    public function shift();
    public function unshift($value);
}

interface IRandomAccessCollection extends IShiftableCollection, IMovable, core\IValueMap, \ArrayAccess {}



// Integer indexes only
interface IIndexedCollection extends IRandomAccessCollection, ISeekable, ISliceable {
    public function put($index, $value);
}

interface ISequentialCollection extends ICollection {
    public function insert($value);
}

// Strict associative indexes
interface IMappedCollection extends ICollection, core\IValueMap, \ArrayAccess {}

// Object access returns container objects, otherwise, same behaviour as mapped
interface IMappedContainerCollection extends IMappedCollection {
    public function __set($key, $value);
    public function __get($key);
    public function __isset($key);
    public function __unset($key);
}





interface IQueue extends ICollection {}
interface IIndexedQueue extends IQueue, IIndexedCollection {}
interface ISequentialQueue extends IQueue, ISequentialCollection {}

interface IPriorityQueue extends IQueue, ISiftingCollection {
    public function insert($value, $priority);
    public function getCurrentPriority();
    public function getPriorityList();
}


interface IStack extends IIndexedCollection {}

interface IMap extends IMappedCollection, ISeekable, ISortable, IMovable {}

interface IHeap extends ISiftingCollection {
    public function insert($value);
}


interface ISet extends ICollection {
    public function add($value);
    public function has($value);
    public function remove($value);
    public function replace($current, $new);
}



interface ITree extends IRandomAccessCollection, IMappedContainerCollection, core\IUserValueContainer, core\IStringProvider {
    public function importTree(ITree $child);
    public function merge(ITree $child);
    public function getNestedChild($parts, $separator='.');
    public function contains($value, $includeChildren=false);
    public function toArrayDelimitedString($setDelimiter='&', $valueDelimiter='=');
    public function toArrayDelimitedSet($prefix=null);
    public function toUrlEncodedArrayDelimitedSet($prefix=null);
    public function hasKey($key);
    public function hasAnyKey($key1);
    public function getKeys();
    public function clearKeys();
    public function getChildren();
    public function hasAnyValue(array $checkKeys=null);
}

interface IInputTree extends ITree, IErrorContainer {
    public function toArrayDelimitedErrorSet($prefix=null);
}



interface IHeaderMap extends IMappedCollection, core\IStringProvider, \Iterator {
    public function getBase($key, $default=null);
    public function append($key, $value);
    public function setNamedValue($key, $name, $keyValue);
    public function getNamedValue($key, $name, $default=null);
    public function hasNamedValue($key, $name);
    public static function normalizeKey($key);
    public function getLines(array $skipKeys=null);
}

interface IHeaderMapProvider {
    public function getHeaders();
    public function setHeaders(IHeaderMap $headers);
    public function hasHeaders();
    public function prepareHeaders();
    public function getHeaderString(array $skipKeys=null);
}


trait THeaderMapProvider {

    protected $_headers;

    public function getHeaders() {
        if(!$this->_headers) {
            $this->_headers = new core\collection\HeaderMap();
        }

        return $this->_headers;
    }

    public function setHeaders(core\collection\IHeaderMap $headers) {
        $this->_headers = $headers;
        return $this;
    }

    public function prepareHeaders() {
        return $this;
    }

    public function hasHeaders() {
        return $this->_headers && !$this->_headers->isEmpty();
    }

    public function getHeaderString(array $skipKeys=null) {
        $this->prepareHeaders();

        if($this->_headers) {
            return $this->_headers->toString($skipKeys);
        }

        return '';
    }
}


// Error Container
interface IErrorContainer {
    public function isValid();
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

trait TErrorContainer {
    
    protected $_errors = [];
    
    public function isValid() {
        return $this->hasErrors();
    }
    
    public function setErrors(array $errors) {
        $this->_errors = [];
        return $this->addErrors($errors);
    }
    
    public function addErrors(array $errors) {
        foreach($errors as $code => $message) {
            $this->addError($code, $message);
        }    
        
        return $this;
    }
    
    public function addError($code, $message) {
        $this->_errors[$code] = $message;
        return $this;
    }
    
    public function getErrors() {
        return $this->_errors;
    }
    
    public function getError($code) {
        if(isset($this->_errors[$code])) {
            return $this->_errors[$code];
        }
        
        return null;
    }
    
    public function hasErrors() {
        return !empty($this->_errors);
    }
    
    public function hasError($code) {
        return isset($this->_errors[$code]);
    }
    
    public function clearErrors() {
        $this->_errors = [];
        return $this;
    }
    
    public function clearError($code) {
        unset($this->_errors[$code]);
        return $this;
    }
}


// Attribute container
interface IAttributeContainer {
    public function setAttributes(array $attributes);
    public function addAttributes(array $attributes);
    public function getAttributes();
    public function setAttribute($key, $value);
    public function getAttribute($key, $default=null);
    public function removeAttribute($key);
    public function hasAttribute($key);
    public function countAttributes();
}

trait TAttributeContainer {
    
    protected $_attributes = [];
    
    public function setAttributes(array $attributes) {
        $this->_attributes = [];
        return $this->addAttributes($attributes);
    }
    
    public function addAttributes(array $attributes) {
        foreach($attributes as $key => $value){
            $this->setAttribute($key, $value);
        }
        
        return $this;
    }
    
    public function getAttributes() {
        return $this->_attributes;
    }
    
    public function setAttribute($key, $value) {
        $this->_attributes[$key] = $value;
        return $this;
    }
    
    public function getAttribute($key, $default=null) {
        if(isset($this->_attributes[$key])) {
            return $this->_attributes[$key];
        }
        
        return $default;
    }
    
    public function removeAttribute($key) {
        unset($this->_attributes[$key]);
        return $this;
    }
    
    public function hasAttribute($key) {
        return isset($this->_attributes[$key]);
    }

    public function countAttributes() {
        return count($this->_attributes);
    }
}

trait TAttributeContainerArrayAccessProxy {

    public function offsetSet($key, $value) {
        return $this->setAttribute($key, $value);
    }
    
    public function offsetGet($key) {
        return $this->getAttribute($key);
    }
    
    public function offsetExists($key) {
        return $this->hasAttribute($key);
    }
    
    public function offsetUnset($key) {
        return $this->removeAttribute($key);
    }
}

trait TArrayAccessedAttributeContainer {
    use TAttributeContainer;
    use TAttributeContainerArrayAccessProxy;
}



// Util
interface IUtil {
    public static function flattenArray($array, $unique=true, $removeNull=false);
    public static function isIterable($collection);
    public static function ensureIterable($collection);

    public static function normalizeEnumValue($value, array $map, $defaultValue=null);
    public static function exportArray(array $values, $level=1);
}