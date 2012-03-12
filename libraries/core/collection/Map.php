<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class Map implements IMap, IAggregateIteratorCollection, core\IDumpable {
    
    use TArrayCollection_Map;
}
