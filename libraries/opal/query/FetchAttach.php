<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class FetchAttach extends Fetch implements IFetchAttachQuery {
    
    use TQuery_Attachment;
    use TQuery_ParentAwareJoinClauseFactory;
    
    public function __construct(IQuery $parent, ISourceManager $sourceManager, ISource $source) {
        $this->_parent = $parent;
        parent::__construct($sourceManager, $source);
        
        $this->_joinClauseList = new opal\query\clause\JoinList($this);
    }
    
    public function getQueryType() {
        return IQueryTypes::FETCH_ATTACH;
    }


// Dump
    public function getDumpProperties() {
        $output = [
            'sources' => $this->_sourceManager,
            'type' => self::typeIdToName($this->_type),
            'fields' => $this->_source,
            'on' => $this->_joinClauseList,
        ];
        

        if(!empty($this->_joinConstraints)) {
            $output['joinConstraint'] = $this->_joinConstraints;
        }
        
        if($this->hasWhereClauses()) {
            $output['where'] = $this->getWhereClauseList();
        }
        
        
        if(!empty($this->_order)) {
            $order = [];
            
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
