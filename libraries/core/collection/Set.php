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

class Set implements ISet, IAggregateIteratorCollection, Inspectable
{
    use TArrayCollection;
    use TArrayCollection_Constructor;
    use TArrayCollection_UniqueSet;

    public function import(...$input)
    {
        $this->_collection = array_unique(array_merge(
            $this->_collection,
            $input
        ));

        return $this;
    }

    public function getReductiveIterator(): \Iterator
    {
        return new ReductiveIndexIterator($this);
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setValues($inspector->inspectList($this->_collection));
    }
}
