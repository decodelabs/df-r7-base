<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class Stack implements IStack, IAggregateIteratorCollection, core\IDumpable {

    use TArrayCollection, TStack;
}
