<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailchimp3;

use df;
use df\core;
use df\spur;
use df\mint;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class DataList extends core\collection\Tree implements IDataList
{
    protected const PROPAGATE_TYPE = false;

    protected $_total = 0;
    protected $_filter;

    public function __construct(string $type, IFilter $filter, core\collection\ITree $data, $callback=null)
    {
        parent::__construct();
        $this->_total = (int)$data['total_items'];

        if (!$keyName = $filter->getKeyName()) {
            throw core\Error::EDefinition('No filter key name set');
        }

        $this->setFilter($filter);

        foreach ($data->{$keyName} as $node) {
            $this->_collection[] = new DataObject($type, $node, $callback);
        }
    }

    public function getTotal(): int
    {
        return $this->_total;
    }

    public function hasMore(): bool
    {
        return $this->_total > count($this->_collection);
    }


    public function setFilter(spur\IFilter $filter)
    {
        $this->_filter = $filter;
        return $this;
    }

    public function getFilter(): spur\IFilter
    {
        return $this->_filter;
    }



    // Serialize
    protected function _getSerializeValues()
    {
        $output = parent::_getSerializeValues();
        $output['tl'] = $this->_total;

        if ($this->_filter) {
            $output['fl'] = $this->_filter;
        }

        return $output;
    }

    protected function _setUnserializedValues(array $values)
    {
        parent::_setUnserializedValues($values);

        $this->_total = $values['tl'] ?? 0;
        $this->_filter = $values['fl'] ?? null;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        if ($this->_value !== null) {
            $entity->setProperty('*value', $inspector($this->_value));
        }

        if ($this->_total > 0) {
            $entity->setProperty('*total', $inspector($this->_total));
        }

        if ($this->_filter !== null) {
            $entity->setProperty('*filter', $inspector($this->_filter));
        }

        $children = [];

        foreach ($this->_collection as $key => $child) {
            if ($child instanceof self && empty($child->_collection) && !isset($child->_total) && !isset($child->_filter)) {
                $children[$key] = $child->_value;
            } else {
                $children[$key] = $child;
            }
        }

        $entity->setValues($inspector->inspectList($children));
    }
}
