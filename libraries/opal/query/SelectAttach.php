<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class SelectAttach extends Select implements ISelectAttachQuery {
    
    use TQuery_Attachment;
    use TQuery_AttachmentListExtension;
    use TQuery_AttachmentValueExtension;
    use TQuery_ParentAwareJoinClauseFactory;
    
    public function __construct(IQuery $parent, ISourceManager $sourceManager, ISource $source) {
        $this->_parent = $parent;
        parent::__construct($sourceManager, $source);
    }
    
    public function getQueryType() {
        return IQueryTypes::SELECT_ATTACH;
    }
     
    
    
// Dump
    public function getDumpProperties() {
        $output = [
            'sources' => $this->_sourceManager,
            'fields' => $this->_source
        ];

        if(!empty($this->_populates)) {
            $output['populates'] = $this->_populates;
        }

        if(!empty($this->_combines)) {
            $output['combines'] = $this->_combines;
        }
        
        if(!empty($this->_joins)) {
            $output['join'] = $this->_joins;
        }
        
        if(!empty($this->_attachments)) {
            $output['attach'] = $this->_attachments;
        }

        if($this->hasWhereClauses()) {
            $output['where'] = $this->getWhereClauseList();
        }
        
        if(!empty($this->_group)) {
            $output['group'] = $this->_group;
        }
        
        if($this->_havingClauseList && !$this->_havingClauseList->isEmpty()) {
            $output['having'] = $this->_havingClauseList;
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
        
        $output['on'] = $this->_joinClauseList;
        $output['type'] = self::typeIdToName($this->_type);
        
        return $output;
    }
}
