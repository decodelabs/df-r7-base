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
        if ($child instanceof IInputTree) {
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
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        if ($this->_value !== null) {
            $entity->setProperty('*value', $inspector($this->_value));
        }

        if (!empty($this->_attributes)) {
            $entity->setProperty('*attributes', $inspector($this->_attributes));
        }

        $children = [];

        foreach ($this->_collection as $key => $child) {
            if ($child instanceof self && empty($child->_collection) && empty($child->_attributes)) {
                $children[$key] = $child->_value;
            } else {
                $children[$key] = $child;
            }
        }

        $entity->setValues($inspector->inspectList($children));
    }
}
