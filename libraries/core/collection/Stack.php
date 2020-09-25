<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

use DecodeLabs\Glitch\Dumpable;

class Stack implements IStack, \IteratorAggregate, Dumpable
{
    use TArrayCollection_Stack;
    use TArrayCollection_Constructor;
}
