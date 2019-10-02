<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class PageableQueue implements IIndexedQueue, IAggregateIteratorCollection, IPaginator, Inspectable
{
    use TArrayCollection_Queue;
    use TPaginator;

    public function __construct(array $input=null, $limit=null, $offset=null, $total=null)
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
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperty('*limit', $inspector($this->_limit))
            ->setProperty('*offset', $inspector($this->_offset))
            ->setProperty('*total', $inspector($this->_total))
            ->setValues($inspector->inspectList($this->_collection));
    }
}
