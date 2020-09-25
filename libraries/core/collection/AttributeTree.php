<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class AttributeTree extends Tree implements IAttributeContainer
{
    use core\collection\TAttributeContainer;

    protected function _getSerializeValues()
    {
        $output = parent::_getSerializeValues();

        if (!empty($this->_attributes)) {
            if ($output === null) {
                $output = [];
            }

            $output['at'] = $this->_attributes;
        }

        return $output;
    }

    protected function _setUnserializedValues(array $values)
    {
        parent::_setUnserializedValues($values);

        if (isset($values['at'])) {
            $this->_attributes = $values['at'];
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
     * Inspect for Glitch
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
                yield 'value:'.$key => $child->_value;
            } else {
                yield 'value:'.$key => $child;
            }
        }
    }
}
