<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use DecodeLabs\Glitch\Dumpable;

class Queue implements IIndexedQueue, \IteratorAggregate, Dumpable
{
    use TArrayCollection_Queue;
    use TArrayCollection_Constructor;
}
