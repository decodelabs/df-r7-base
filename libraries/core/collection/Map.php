<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use DecodeLabs\Glitch\Dumpable;

class Map implements IMap, \IteratorAggregate, Dumpable
{
    use TArrayCollection_Map;
    use TArrayCollection_Constructor;
}
