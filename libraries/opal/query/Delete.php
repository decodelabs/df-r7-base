<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Delete implements IDeleteQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_PrerequisiteAwareWhereClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;
    
    public function __construct(ISourceManager $sourceManager, ISource $source) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
    }
    
    public function getQueryType() {
        return IQueryTypes::DELETE;
    }
    
    
// Execute
    public function execute() {
        $adapter = $this->_source->getAdapter();
        
        try {
            return $adapter->executeDeleteQuery($this);
        } catch(\Exception $e) {
            if($this->_sourceManager->handleQueryException($this, $e)) {
                return $adapter->executeDeleteQuery($this);
            } else {
                throw $e;
            }
        }
    }
    
    
    
// Dump
    public function getDumpProperties() {
        $output = array(
            'source' => $this->_source->getAdapter()
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
