<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\collection;

class SeekableIterator implements \Iterator
{
    protected $_collection;

    public function __construct(ISeekable $collection)
    {
        $this->_collection = $collection;
    }

    public function current(): mixed
    {
        return $this->_collection->getCurrent();
    }

    public function next(): void
    {
        $this->_collection->seekNext();
    }

    public function key(): int
    {
        return $this->_collection->getSeekPosition();
    }

    public function valid(): bool
    {
        return !$this->_collection->hasSeekEnded();
    }

    public function rewind(): void
    {
        $this->_collection->seekFirst();
    }
}
