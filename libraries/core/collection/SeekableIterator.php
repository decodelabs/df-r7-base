<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class SeekableIterator implements \Iterator
{
    protected $_collection;

    public function __construct(ISeekable $collection)
    {
        $this->_collection = $collection;
    }

    public function current()
    {
        return $this->_collection->getCurrent();
    }

    public function next()
    {
        $this->_collection->seekNext();
    }

    public function key()
    {
        return $this->_collection->getSeekPosition();
    }

    public function valid()
    {
        return !$this->_collection->hasSeekEnded();
    }

    public function rewind()
    {
        $this->_collection->seekFirst();
    }
}
