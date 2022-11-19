<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\collection;

class ReductiveIndexIterator implements \Iterator
{
    protected $_collection;
    protected $_pos = 0;
    protected $_row;

    public function __construct(ICollection $collection)
    {
        $this->_collection = $collection;
    }

    public function current(): ?array
    {
        if ($this->_row === null) {
            $this->_row = $this->_collection->extract();
        }

        return $this->_row;
    }

    public function next(): void
    {
        $this->_pos++;
        $this->_row = null;
    }

    public function key(): int
    {
        return $this->_pos;
    }

    public function valid(): bool
    {
        return !$this->_collection->isEmpty();
    }

    public function rewind(): void
    {
        $this->_pos = 0;

        if ($this->_collection instanceof ISeekable) {
            $this->_collection->seekFirst();
        }
    }
}
