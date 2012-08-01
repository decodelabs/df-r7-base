<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;
    
class Correlation implements ICorrelationQuery, core\IDumpable {

    use TQuery;
    use TQuery_ParentAware;
    use TQuery_ParentAwareJoinClauseFactory;
    use TQuery_JoinConstrainable;
    use TQuery_WhereClauseFactory;
    use TQuery_Limitable;
    use TQuery_Offsettable;

    protected $_source;
    protected $_fieldAlias;
    protected $_applicator;

    public function __construct(ISourceProvider $parent, ISource $source, $fieldAlias=null) {
    	$this->_parent = $parent;
    	$this->_source = $source;
    	$this->_fieldAlias = $fieldAlias;

    	if($this->_fieldAlias === null) {
    		$field = $this->_source->getFirstOutputDataField();
    		$this->_fieldAlias = $field->getAlias();
    	}
    }

    public function getQueryType() {
        return IQueryTypes::CORRELATION;
    }

    public function __clone() {
        if($this->_joinClauseList) {
            $this->_joinClauseList = clone $this->_joinClauseList;
        }

        if($this->_whereClauseList) {
            $this->_whereClauseList = clone $this->_whereClauseList;
        }
    }


// Sources
    public function getSourceManager() {
        return $this->_parent->getSourceManager();
    }
    
    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }

// Correlation
    public function getFieldAlias() {
    	return $this->_fieldAlias;
    }


// Applicator
    public function setApplicator(Callable $applicator=null) {
        $this->_applicator = $applicator;
        return $this;
    }

    public function getApplicator() {
        return $this->_applicator;
    }

    public function endCorrelation($fieldAlias=null) {
        if($fieldAlias !== null) {
            $this->_fieldAlias = $fieldAlias;
        }

        if($this->_applicator) {
            $this->_applicator->__invoke($this);
        } else if($this->_parent instanceof ICorrelatableQuery) {
           $this->_parent->addCorrelation($this);
        }

        return $this->_parent;
    }

    public function getCorrelationSource() {
        $parent = $this->_parent;

        while($parent instanceof IParentQueryAware) {
            $parent = $parent->getParentQuery();
        }

        return $parent->getSource();
    }

    public function getCorrelatedClauses(ISource $correlationSource=null) {
        if($correlationSource === null) {
            $correlationSource = $this->getCorrelationSource();
        }

        $clauses = $this->_joinClauseList->extractClausesFor($correlationSource);

        if($this->_whereClauseList) {
            $clauses = array_merge(
                $clauses, $this->_whereClauseList->extractClausesFor($correlationSource)
            );
        }

        return $clauses;
    }

// Dump
    public function getDumpProperties() {
    	$output = [
    		'fieldAlias' => $this->_fieldAlias,
    		'fields' => $this->_source,
    		'on' => $this->_joinClauseList
    	];

        if(!empty($this->_joinConstraints)) {
            $output['joinConstraints'] = $this->_joinConstraints;
        }

        if($this->_whereClauseList && !$this->_whereClauseList->isEmpty()) {
            $output['where'] = $this->_whereClauseList;
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