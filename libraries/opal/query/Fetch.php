<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Fetch implements IFetchQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Correlatable;
    use TQuery_JoinConstrainable;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_PrerequisiteAwareWhereClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Offsettable;
    use TQuery_Populatable;
    use TQuery_Pageable;
    use TQuery_Read;
    
    public function __construct(ISourceManager $sourceManager, ISource $source) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
    }
    
    public function getQueryType() {
        return IQueryTypes::FETCH;
    }

    
    
// Output
    public function count() {
        return $this->_sourceManager->executeQuery($this, function($adapter) {
            return (int)$adapter->countFetchQuery($this);
        });
    }

    protected function _fetchSourceData($keyField=null) {
        if($keyField !== null) {
            $keyField = $this->_sourceManager->extrapolateDataField($this->_source, $keyField);
        }
        
        $output = $this->_sourceManager->executeQuery($this, function($adapter) use($keyField) {
            return $adapter->executeFetchQuery($this, $keyField);
        });

        if($this->_paginator && $this->_offset == 0 && $this->_limit) {
            $count = count($output);

            if($count < $this->_limit) {
                $this->_paginator->setTotal($count);
            }
        }

        return $output;
    }
    
// Dump
    public function getDumpProperties() {
        $output = array(
            'sources' => $this->_sourceManager,
            'fields' => $this->_source
        );

        if(!empty($this->_populates)) {
            $output['populates'] = $this->_populates;
        }
        
        if(!empty($this->_joinConstraints)) {
            $output['joinConstraints'] = $this->_joinConstraints;
        }
        
        if($this->hasWhereClauses()) {
            $output['where'] = $this->getWhereClauseList();
        }
        
        if(!empty($this->_order)) {
            $order = array();
            
            foreach($this->_order as $directive) {
                $order[] = $directive->toString();
            }
            
            $output['order'] = implode(', ', $order);
        }
        
        if($this->_limit) {
            $output['limit'] = $this->_limit;
        }
        
        if($this->_offset) {
            $output['offset'] = $this->_offset;
        }
        
        return $output;
    }
}
