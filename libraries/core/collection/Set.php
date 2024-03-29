<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use DecodeLabs\Glitch\Dumpable;

class Set implements ISet, \IteratorAggregate, Dumpable
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

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'values' => $this->_collection;
    }
}
