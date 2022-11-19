<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\collection;

use df\core;

class AttributeTree extends Tree implements IAttributeContainer
{
    use core\collection\TAttributeContainer;

    public function __serialize(): array
    {
        $output = parent::__serialize();

        if (!empty($this->_attributes)) {
            $output = [];
            $output['at'] = $this->_attributes;
        }

        return $output;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        if (isset($data['at'])) {
            $this->_attributes = $data['at'];
        }
    }

    public function importTree(ITree $child)
    {
        if ($child instanceof IAttributeContainer) {
            $this->_attributes = $child->getAttributes();
        }

        return parent::importTree($child);
    }

    public function merge(ITree $child)
    {
        if ($child instanceof IAttributeContainer) {
            $this->_attributes = array_merge(
                $this->_attributes,
                $child->getAttributes()
            );
        }

        return parent::importTree($child);
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if ($this->_value !== null) {
            yield 'property:*value' => $this->_value;
        }

        if (!empty($this->_attributes)) {
            yield 'property:*attributes' => $this->_attributes;
        }

        foreach ($this->_collection as $key => $child) {
            if ($child instanceof self && empty($child->_collection) && empty($child->_attributes)) {
                yield 'value:' . $key => $child->_value;
            } else {
                yield 'value:' . $key => $child;
            }
        }
    }
}
