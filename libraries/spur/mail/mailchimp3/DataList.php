<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\mail\mailchimp3;

use DecodeLabs\Exceptional;
use df\core;

use df\spur;

class DataList extends core\collection\Tree implements IDataList
{
    protected const PROPAGATE_TYPE = false;

    protected $_total = 0;
    protected $_filter;

    public function __construct(string $type, IFilter $filter, core\collection\ITree $data, $callback = null)
    {
        parent::__construct();
        $this->_total = (int)$data['total_items'];

        if (!$keyName = $filter->getKeyName()) {
            throw Exceptional::Definition(
                'No filter key name set'
            );
        }

        $this->setFilter($filter);

        foreach ($data->{$keyName} as $node) {
            $this->_collection[] = new DataObject($type, $node, $callback);
        }
    }

    public function getTotal(): int
    {
        return $this->_total;
    }

    public function hasMore(): bool
    {
        return $this->_total > count($this->_collection);
    }


    public function setFilter(spur\IFilter $filter)
    {
        $this->_filter = $filter;
        return $this;
    }

    public function getFilter(): spur\IFilter
    {
        return $this->_filter;
    }



    // Serialize
    public function __serialize(): array
    {
        $output = parent::__serialize();
        $output['tl'] = $this->_total;

        if ($this->_filter) {
            $output['fl'] = $this->_filter;
        }

        return $output;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->_total = $data['tl'] ?? 0;
        $this->_filter = $data['fl'] ?? null;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if ($this->_value !== null) {
            yield 'property:*value' => $this->_value;
        }

        if ($this->_total > 0) {
            yield 'property:*total' => $this->_total;
        }

        if ($this->_filter !== null) {
            yield 'property:*filter' => $this->_filter;
        }

        foreach ($this->_collection as $key => $child) {
            if ($child instanceof self && empty($child->_collection) && !isset($child->_total) && !isset($child->_filter)) {
                yield 'value:' . $key => $child->_value;
            } else {
                yield 'value:' . $key => $child;
            }
        }
    }
}
