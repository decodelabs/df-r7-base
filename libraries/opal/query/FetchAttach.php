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
    
    public function __construct(IReadQuery $parent, ISourceManager $sourceManager, ISource $source) {
        $this->_parent = $parent;
        parent::__construct($sourceManager, $source);
        
        $this->_joinClauseList = new opal\query\clause\JoinList($this);
    }
    
    public function getQueryType() {
        return IQueryTypes::FETCH_ATTACH;
    }
} 
