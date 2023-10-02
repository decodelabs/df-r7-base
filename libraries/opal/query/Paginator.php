<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\query;

use DecodeLabs\Glitch\Dumpable;

use df\core;

class Paginator implements IPaginator, Dumpable
{
    use core\collection\TPaginator;

    protected $_orderableFields = [];
    protected $_order = [];
    protected $_isApplied = false;
    protected $_query;

    public function __construct(IReadQuery $query)
    {
        $this->_query = $query;

        $adapter = $query->getSource()->getAdapter();

        if ($adapter instanceof IPaginatingAdapter) {
            $adapter->applyPagination($this);
        }

        if ($query instanceof ICorrelatableQuery) {
            foreach ($query->getCorrelations() as $name => $correlation) {
                $this->_orderableFields[$correlation->getAlias()] = new OrderDirective($correlation, 'ASC');
            }
        }
    }


    // Orderable fields
    public function setOrderableFields(...$fields)
    {
        $this->_orderableFields = [];
        return $this->addOrderableFields(...$fields);
    }

    public function addOrderableFields(...$fields)
    {
        $source = $this->_query->getSource();
        $sourceManager = $this->_query->getSourceManager();

        foreach ($fields as $key => $field) {
            $parts = explode(' as ', $field);
            $field = (string)array_shift($parts);
            $key = trim((string)array_shift($parts));

            $parts = explode(' ', $field);
            $field = (string)array_shift($parts);
            $direction = (string)array_shift($parts);

            $field = $sourceManager->extrapolateField($source, $field);

            if (empty($key)) {
                $key = $field->getAlias();
            }

            $this->_orderableFields[$key] = new OrderDirective(
                $field,
                $direction
            );
        }

        return $this;
    }

    public function getOrderableFieldDirectives()
    {
        return $this->_orderableFields;
    }

    public function getOrderableFields()
    {
        $output = [];

        foreach ($this->_orderableFields as $key => $directive) {
            $output[$key] = $directive->getField();
        }

        return $output;
    }

    public function getOrderableFieldNames()
    {
        return array_keys($this->_orderableFields);
    }


    // Default order
    public function setDefaultOrder(...$fields)
    {
        $source = $this->_query->getSource();
        $sourceManager = $this->_query->getSourceManager();
        $this->_order = [];

        foreach (core\collection\Util::leaves($fields) as $field) {
            $parts = explode(' ', $field);
            $key = array_shift($parts);

            if (isset($this->_orderableFields[$key])) {
                $field = $this->_orderableFields[$key]->getField();
            } else {
                $field = $sourceManager->extrapolateField($source, $key);
                $key = $field->getAlias();
            }

            $directive = new OrderDirective(
                $field,
                array_shift($parts)
            );

            $this->_order[$key] = $directive;
        }

        return $this;
    }

    public function getOrderDirectives()
    {
        return $this->_order;
    }

    public function getFirstOrderDirective()
    {
        $temp = $this->_order;
        return array_shift($temp);
    }

    public function getOrderString()
    {
        if (empty($this->_order)) {
            $fields = $this->getOrderableFieldNames();
            return array_shift($fields) . ' ASC';
        }

        $output = [];

        foreach ($this->_order as $directive) {
            $output[] = $directive->getField()->getAlias() . ' ' . $directive->getDirection();
        }

        return implode(',', $output);
    }

    public function getFirstOrderString()
    {
        if (!$directive = $this->getFirstOrderDirective()) {
            $fields = $this->getOrderableFieldNames();
            return array_shift($fields) . ' ASC';
        }

        return $directive->getField()->getAlias() . ' ' . $directive->getDirection();
    }


    // Limit
    public function setDefaultLimit($limit)
    {
        $this->_limit = (int)$limit;

        if ($this->_limit < 1) {
            $this->_limit = null;
        }

        return $this;
    }


    // Offset
    public function setDefaultOffset($offset)
    {
        $this->_offset = (int)$offset;
        return $this;
    }


    // Key map
    public function setKeyMap(array $map)
    {
        foreach ($this->_keyMap as $key => $val) {
            if (isset($map[$key])) {
                $this->_keyMap[$key] = $map[$key];
            }
        }

        return $this;
    }


    // IO
    public function end()
    {
        $this->_query->setPaginator($this);
        return $this->_query;
    }

    public function applyWith($data)
    {
        if (empty($this->_order) && !empty($this->_orderableFields)) {
            // Set first orderable field as default

            foreach ($this->_orderableFields as $key => $directive) {
                $this->setDefaultOrder($key . ' ASC');
                break;
            }
        }

        $source = $this->_query->getSource();
        $sourceManager = $this->_query->getSourceManager();

        if ($this->_query instanceof ISearchableQuery
        && $this->_query->hasSearch()) {
            $search = $this->_query->getSearch();

            $directive = new OrderDirective(
                $search,
                'DESC'
            );

            $this->_order = array_merge([$search->getAlias() => $directive], $this->_order);
            $this->addOrderableFields($search->getAlias() . ' DESC');
        }

        if (!$data instanceof core\collection\ITree) {
            $data = new core\collection\Tree($data);
        }

        if ($data->has($this->_keyMap['limit'])) {
            $this->setDefaultLimit($data[$this->_keyMap['limit']]);
            $this->_query->limit($this->_limit);
        } elseif ($this->_limit && !$this->_query->hasLimit()) {
            $this->_query->limit($this->_limit);
        }

        if ($data->has($this->_keyMap['offset'])) {
            $this->setDefaultOffset($data[$this->_keyMap['offset']]);
            $this->_query->offset($this->_offset);
        } elseif ($data->has($this->_keyMap['page'])) {
            $page = (int)$data[$this->_keyMap['page']];

            if ($page < 1) {
                $page = 1;
            }

            $this->setDefaultOffset($this->_limit * ($page - 1));
            $this->_query->offset($this->_offset);
        } elseif ($this->_offset && !$this->_query->hasOffset()) {
            $this->_query->offset($this->_offset);
        }

        if ($data->has($this->_keyMap['order']) && !empty($this->_orderableFields)) {
            $orderNode = $data->{$this->_keyMap['order']};
            $orderList = [];

            if (count($orderNode)) {
                $order = $orderNode->toArray();
            } else {
                $order = explode(',', $orderNode->getValue());
            }

            foreach ($order as $part) {
                $t = explode(' ', trim((string)$part), 2);
                $key = trim((string)$t[0]);

                if (isset($this->_orderableFields[$key])) {
                    $dir = 'ASC';

                    if (isset($t[1])) {
                        $dir = trim(strtoupper($t[1]));
                    }

                    $orderList[$key] = new OrderDirective($this->_orderableFields[$key]->getField(), $dir);
                }
            }

            if (!empty($orderList)) {
                $this->_order = $orderList;
            }

            $this->_query->setOrderDirectives($this->_order);
        } elseif (!empty($this->_order) && !$this->_query->hasOrderDirectives()) {
            $this->_query->setOrderDirectives($this->_order);
        }

        $this->_isApplied = true;

        return $this->_query->setPaginator($this);
    }

    public function isApplied()
    {
        return $this->_isApplied;
    }

    public function setTotal(?int $total)
    {
        $this->_total = $total;
        return $this;
    }

    public function countTotal(): ?int
    {
        if ($this->_total === null) {
            $this->_total = $this->_query->count();
        }

        return $this->_total;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if (!empty($this->_orderableFields)) {
            yield 'property:*orderableFields' => implode(', ', $this->_orderableFields);
        }

        if (!empty($this->_order)) {
            yield 'property:*defaultOrder' => implode(', ', $this->_order);
        }

        if ($this->_limit) {
            yield 'property:*defaultLimit' => $this->_limit;
        }

        if ($this->_offset) {
            yield 'property:*defaultOffset' => $this->_offset;
        }
    }
}
