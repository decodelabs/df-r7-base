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

    use TQuery_ParentAware;
    use TQuery_ParentAwareJoinClauseFactory;
    use TQuery_AccessLock;

    protected $_source;
    protected $_fieldAlias;

    public function __construct(ICorrelatableQuery $parent, ISource $source, $fieldAlias=null) {
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

    public function endCorrelation($fieldAlias=null) {
    	if($fieldAlias !== null) {
    		$this->_fieldAlias = $fieldAlias;
    	}

    	$this->_parent->addCorrelation($this);
    	return $this->_parent;
    }


// Dump
    public function getDumpProperties() {
    	$output = [
    		'fieldAlias' => $this->_fieldAlias,
    		'fields' => $this->_source,
    		'on' => $this->_joinClauseList
    	];

        return $output;
    }
}