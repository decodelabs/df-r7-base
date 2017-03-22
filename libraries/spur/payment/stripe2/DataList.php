<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2;

use df;
use df\core;
use df\spur;
use df\mint;

class DataList implements IList {

    protected $_total = 0;
    protected $_hasMore = false;
    protected $_startingAfter;
    protected $_endingBefore;
    protected $_filter;
    protected $_objects = [];

    public function __construct(string $type, IFilter $filter=null, core\collection\ITree $data, $callback=null) {
        if($filter) {
            $this->setFilter($filter);
        }

        $this->_total = (int)$data['total_count'];
        $this->_hasMore = (bool)$data['has_more'];

        $first = true;

        foreach($data->data as $node) {
            $this->_objects[] = $object = new DataObject($type, $node, $callback);

            if($first) {
                $this->_endingBefore = $object['id'];
                $first = false;
            }
        }

        if($object) {
            $this->_startingAfter = $object['id'];
        }
    }

    public function getTotal(): int {
        return $this->_total;
    }

    public function hasMore(): bool {
        return $this->_hasMore;
    }


    public function setFilter(IFilter $filter) {
        $this->_filter = $filter;
        return $this;
    }

    public function getFilter(): IFilter {
        return $this->_filter;
    }


    public function getStartingAfter()/*: ?string*/ {
        return $this->_startingAfter;
    }

    public function getEndingBefore()/*: ?string*/ {
        return $this->_endingBefore;
    }


    public function toArray(): array {
        return $this->_objects;
    }

    public function getIterator() {
        return new \ArrayIterator($this->_objects);
    }
}