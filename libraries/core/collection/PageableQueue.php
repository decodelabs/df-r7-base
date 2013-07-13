<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class PageableQueue implements IIndexedQueue, IAggregateIteratorCollection, IPaginator, core\IDumpable {
    
    use TArrayCollection_Queue;
    use TPaginator;

    public function __construct($input=null, $limit=null, $offset=null, $total=null) {
        if($input !== null) {
            $this->import($input);
        }

        $this->setLimit($limit);
        $this->setOffset($offset);
        $this->setTotal($total);
    }

    public function setLimit($limit) {
        $this->_limit = $limit;
        return $this;
    }

    public function setOffset($offset) {
        $this->_offset = $offset;
        return $this;
    }

    public function setTotal($total) {
        $this->_total = $total;
        return $this;
    }

    public function countTotal() {
        if($this->_total === null) {
            return count($this->_collection);
        }

        return $this->_total;
    }

    public function setKeyMap(array $keyMap) {
        $this->_keyMap = $keyMap;
        return $this;
    }
}
