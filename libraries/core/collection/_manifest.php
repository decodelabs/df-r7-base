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
        $output = array();
        
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
    public function sortByValue();
    public function reverseSortByValue();
    public function sortByKey();
    public function reverseSortByKey();
    public function reverse();
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


// Paging
interface IPageable {
    public function setPaginator(IPaginator $paginator);
    public function getPaginator();
}

interface IPaginator {
    public function getLimit();
    public function getOffset();
    public function countTotal();
    public function getKeyMap();
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

interface IRandomAccessCollection extends IShiftableCollection, core\IValueMap, \ArrayAccess {}



// Integer indexes only
interface IIndexedCollection extends IRandomAccessCollection, ISeekable {
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

interface IMap extends IMappedCollection, ISeekable, ISortable {}

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
    public function getKeys();
    public function clearKeys();
}

interface IInputTree extends ITree, core\IErrorContainer {}