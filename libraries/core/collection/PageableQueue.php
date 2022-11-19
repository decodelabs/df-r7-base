<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use DecodeLabs\Glitch\Dumpable;

class PageableQueue implements IIndexedQueue, \IteratorAggregate, IPaginator, Dumpable
{
    use TArrayCollection_Queue;
    use TPaginator;

    public function __construct(array $input = null, $limit = null, $offset = null, $total = null)
    {
        if ($input !== null) {
            $this->import(...$input);
        }

        $this->setLimit($limit);
        $this->setOffset($offset);
        $this->setTotal($total);
    }

    public function setLimit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    public function setOffset($offset)
    {
        $this->_offset = $offset;
        return $this;
    }

    public function setTotal(?int $total)
    {
        $this->_total = $total;
        return $this;
    }

    public function countTotal(): ?int
    {
        if ($this->_total === null) {
            return count($this->_collection);
        }

        return $this->_total;
    }

    public function setKeyMap(array $keyMap)
    {
        $this->_keyMap = $keyMap;
        return $this;
    }

    public function toArray(): array
    {
        return $this->_collection;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*limit' => $this->_limit,
            '*offset' => $this->_offset,
            '*total' => $this->_total
        ];

        yield 'values' => $this->_collection;
    }
}
