<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Update implements IUpdateQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_DataUpdate;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_PrerequisiteAwareWhereClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;
    
    public function __construct(ISourceManager $sourceManager, ISource $source, array $valueMap=null) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        
        if($valueMap !== null) {
            $this->set($valueMap);
        }
    }
    
    public function getQueryType() {
        return IQueryTypes::UPDATE;
    }
    
    
    
    
// Execute
    public function execute() {
        $adapter = $this->_source->getAdapter();
        
        if($adapter instanceof IIntegralAdapter) {
            $this->_valueMap = $adapter->deflateUpdateValues($this->_valueMap);
        }
        
        if(empty($this->_valueMap)) {
            return 0;
        }
        
        
        try {
            return $adapter->executeUpdateQuery($this);
        } catch(\Exception $e) {
            if($this->_sourceManager->handleQueryException($this, $e)) {
                return $adapter->executeUpdateQuery($this);
            } else {
                throw $e;
            }
        }
    }
    
    
    
// Dump
    public function getDumpProperties() {
        $output = array(
            'source' => $this->_source->getAdapter(),
            'valueMap' => $this->_valueMap
        );
        
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
        
        if($this->_limit !== null) {
            $output['limit'] = $this->_limit;
        }
        
        return $output;
    }
}
