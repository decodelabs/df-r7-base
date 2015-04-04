<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Paginator implements IPaginator {
    
    use core\collection\TPaginator;

    protected $_orderableFields = [];
    protected $_order = [];
    protected $_isApplied = false;
    protected $_query;
    
    public function __construct(IReadQuery $query) {
        $this->_query = $query;

        $adapter = $query->getSource()->getAdapter();

        if($adapter instanceof IPaginatingAdapter) {
            $adapter->applyPagination($this);
        }

        if($query instanceof ICorrelatableQuery) {
            foreach($query->getCorrelations() as $name => $correlation) {
                $this->_orderableFields[$correlation->getAlias()] = $correlation;
            }
        }
    }
    
    
// Orderable fields
    public function setOrderableFields($fields) {
        $this->_orderableFields = [];

        if(!is_array($fields)) {
            $fields = func_get_args();
        }

        return $this->addOrderableFields($fields);
    }

    public function addOrderableFields($fields) {
        $source = $this->_query->getSource();
        $sourceManager = $this->_query->getSourceManager();
        
        if(!is_array($fields)) {
            $fields = func_get_args();
        }
        
        foreach($fields as $key => $field) {
            $parts = explode(' as ', $field);
            $field = $sourceManager->extrapolateField($source, array_shift($parts));

            if(!empty($parts)) {
                $key = trim(array_shift($parts));
            }
            
            if(!is_string($key)) {
                $key = $field->getAlias();
            }

            $this->_orderableFields[$key] = $field;
        }

        return $this;
    }

    public function getOrderableFields() {
        return $this->_orderableFields;
    }
    
    public function getOrderableFieldNames() {
        return array_keys($this->_orderableFields);
    }
    

// Default order
    public function setDefaultOrder($field1) {
        $source = $this->_query->getSource();
        $sourceManager = $this->_query->getSourceManager();
        $this->_order = [];
        
        foreach(func_get_args() as $field) {
            $parts = explode(' ', $field);
            $key = array_shift($parts);
            
            if(isset($this->_orderableFields[$key])) {
                $field = $this->_orderableFields[$key];
            } else {
                $field = $sourceManager->extrapolateField($source, $key);
                $key = $field->getAlias();
            }
            
            $directive = new OrderDirective(
                $field, array_shift($parts)
            );
            
            $this->_order[$key] = $directive;
        }
        
        return $this;
    }
    
    public function getOrderDirectives() {
        return $this->_order;
    }

    public function getFirstOrderDirective() {
        $temp = $this->_order;
        return array_shift($temp);
    }

    public function getOrderString() {
        if(empty($this->_order)) {
            $fields = $this->getOrderableFieldNames();
            return array_shift($fields).' ASC';
        }

        $output = [];

        foreach($this->_order as $directive) {
            $output[] = $directive->getField()->getAlias().' '.$directive->getDirection();
        }

        return implode(',', $output);
    }

    public function getFirstOrderString() {
        if(!$directive = $this->getFirstOrderDirective()) {
            $fields = $this->getOrderableFieldNames();
            return array_shift($fields).' ASC';
        }

        return $directive->getField()->getAlias().' '.$directive->getDirection();
    }
    

// Limit
    public function setDefaultLimit($limit) {
        $this->_limit = (int)$limit;
        
        if($this->_limit < 1) {
            $this->_limit = null;
        }
        
        return $this;
    }
    

// Offset
    public function setDefaultOffset($offset) {
        $this->_offset = (int)$offset;
        return $this;
    }
    
    
// Key map
    public function setKeyMap(array $map) {
        foreach($this->_keyMap as $key => $val) {
            if(isset($map[$key])) {
                $this->_keyMap[$key] = $map[$key];
            }
        }
        
        return $this;
    }
    

// IO
    public function end() {
        $this->_query->setPaginator($this);
        return $this->_query;
    }

    public function applyWith($data) {
        if(empty($this->_order) && !empty($this->_orderableFields)) {
            // Set first orderable field as default
            
            foreach($this->_orderableFields as $key => $field) {
                $this->setDefaultOrder($key.' ASC');
                break;
            }
        }

        $source = $this->_query->getSource();
        $sourceManager = $this->_query->getSourceManager();

        if($this->_query instanceof ISearchableQuery
        && $this->_query->hasSearch()) {
            $search = $this->_query->getSearch();

            $directive = new OrderDirective(
                $search, 'DESC'
            );

            $this->_order = array_merge([$search->getAlias() => $directive], $this->_order);
            $this->addOrderableFields($search->getAlias());
        }
        
        if(!$data instanceof core\collection\ITree) {
            $data = new core\collection\Tree($data);
        }
        
        if($data->has($this->_keyMap['limit'])) {
            $this->setDefaultLimit($data[$this->_keyMap['limit']]);
        }
        
        if($data->has($this->_keyMap['offset'])) {
            $this->setDefaultOffset($data[$this->_keyMap['offset']]);
        } else if($data->has($this->_keyMap['page'])) {
            $page = (int)$data[$this->_keyMap['page']];
            
            if($page < 1) {
                $page = 1;
            }
            
            $this->setDefaultOffset($this->_limit * ($page - 1));
        }
        
        if($data->has($this->_keyMap['order']) && !empty($this->_orderableFields)) {
            $orderNode = $data->{$this->_keyMap['order']};
            $orderList = [];
            
            if(count($orderNode)) {
                $order = $orderNode->toArray();
            } else {
                $order = explode(',', $orderNode->getValue());
            }
            
            foreach($order as $part) {
                $t = explode(' ', trim($part), 2);
                $key = trim($t[0]);
                
                if(isset($this->_orderableFields[$key])) {
                    $dir = 'ASC';
                    
                    if(isset($t[1])) {
                        $dir = trim(strtoupper($t[1]));
                    }
                    
                    $orderList[$key] = new OrderDirective($this->_orderableFields[$key], $dir);
                }
            }
            
            if(!empty($orderList)) {
                $this->_order = $orderList;
            }
        }

        $this->_isApplied = true;

        return $this->_query->setPaginator($this)
            ->limit($this->_limit)
            ->offset($this->_offset)
            ->setOrderDirectives($this->_order);
    }

    public function isApplied() {
        return $this->_isApplied;
    }

    public function setTotal($total) {
        if($total !== null) {
            $total = (int)$total;
        }

        $this->_total = $total;
        return $this;
    }

    public function countTotal() {
        if($this->_total === null) {
            $this->_total = $this->_query->count();
        }
        
        return $this->_total;
    }
}
