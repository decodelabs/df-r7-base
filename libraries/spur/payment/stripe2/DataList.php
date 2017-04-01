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

class DataList extends core\collection\Tree implements IDataList {

    protected $_total = 0;
    protected $_hasMore = false;
    protected $_startingAfter;
    protected $_endingBefore;
    protected $_filter;

    public function __construct(string $type, IFilter $filter=null, core\collection\ITree $data, $callback=null) {
        $this->_total = (int)$data['total_count'];
        $this->_hasMore = (bool)$data['has_more'];

        $first = true;
        $object = null;
        $hasPointer = false;

        if($filter) {
            $this->setFilter($filter);
            $hasPointer = $filter->hasPointer();
        }

        foreach($data->data as $node) {
            $this->_collection[] = $object = new DataObject($type, $node, $callback);

            if($first && $hasPointer) {
                $this->_endingBefore = $object['id'];
                $first = false;
            }
        }

        if($object && $this->_hasMore) {
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


    public function getStartingAfter(): ?string {
        return $this->_startingAfter;
    }

    public function getEndingBefore(): ?string {
        return $this->_endingBefore;
    }


// Serialize
    protected function _getSerializeValues() {
        $output = parent::_getSerializeValues();

        $output['tl'] = $this->_total;

        if($this->_startingAfter !== null) {
            $output['sa'] = $this->_startingAfter;
        }
        if($this->_endingBefore !== null) {
            $output['eb'] = $this->_endingBefore;
        }

        if($this->_filter) {
            $output['fl'] = $this->_filter;
        }

        return $output;
    }

    protected function _setUnserializedValues(array $values) {
        parent::_setUnserializedValues($values);

        $this->_total = $values['tl'] ?? 0;
        $this->_startingAfter  = $values['sa'] ?? null;
        $this->_endingBefore = $values['eb'] ?? null;
        $this->_filter = $values['fl'] ?? null;
    }


// Dump
    public function getDumpProperties() {
        $output = [
            new core\debug\dumper\Property('total', $this->_total, 'private'),
            new core\debug\dumper\Property('hasMore', $this->_hasMore, 'private'),
            new core\debug\dumper\Property('startingAfter', $this->_startingAfter, 'private'),
            new core\debug\dumper\Property('endingBefore', $this->_endingBefore, 'private'),
            new core\debug\dumper\Property('filter', $this->_filter, 'private')
        ];

        foreach($this->_collection as $key => $child) {
            $output[] = new core\debug\dumper\Property($key, $child, 'public');
        }

        return $output;
    }
}