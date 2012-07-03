<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;


trait TQuery_AdapterAware {
    
    protected $_adapter;
    private $_adapterHash;
    
    public function getAdapter() {
        return $this->_adapter;
    }
    
    public function getAdapterHash() {
        if($this->_adapterHash === null) {
            $this->_adapterHash = $this->_adapter->getQuerySourceAdapterHash();
        }
        
        return $this->_adapterHash; 
    }
}


trait TQuery_TransactionAware {
    
    protected $_transaction;
    
    public function setTransaction(ITransaction $transaction=null) {
        $this->_transaction = $transaction;
        return $this;
    }
    
    public function getTransaction() {
        return $this->_transaction;
    }
}


trait TQuery_ParentAware {
    
    protected $_parent;
    
    public function getParentSourceManager() {
        return $this->_parent->getSourceManager();
    }
    
    public function getParentSource() {
        return $this->_parent->getSource();
    }
}



/*************************
 * Base
 */
trait TQuery {
    
    protected $_sourceManager;
    protected $_source;
    
    public function getSourceManager() {
        return $this->_sourceManager;
    }
    
    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }
}





/****************************
 * Joins
 */
trait TQuery_Joinable {
    
    protected $_joins = array();
    
    public function join($field1=null) {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->setTransaction($this->_sourceManager->getTransaction())
            ->beginJoin($this, func_get_args(), IJoinQuery::INNER);
    }
    
    public function leftJoin($field1=null) {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->setTransaction($this->_sourceManager->getTransaction())
            ->beginJoin($this, func_get_args(), IJoinQuery::LEFT);
    }
    
    public function rightJoin($field1=null) {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->setTransaction($this->_sourceManager->getTransaction())
            ->beginJoin($this, func_get_args(), IJoinQuery::RIGHT);
    }
    
    public function addJoin(IJoinQuery $join) {
        if(!$this->_source->getAdapter()->supportsQueryType($join->getQueryType())) {
            throw new LogicException(
                'Query adapter '.$this->_source->getAdapter()->getQuerySourceDisplayName().' does not support joins'
            );
        }
        
        $this->_joins[$join->getSourceAlias()] = $join;
        return $this;
    }
    
    public function getJoins() {
        return $this->_joins;
    }
    
    public function clearJoins() {
        foreach($this->_joins as $sourceAlias => $join) {
            $this->_sourceManager->removeSource($sourceAlias);
        }
        
        $this->_joins = array();
        return $this;
    }
}



trait TQuery_JoinConstrainable {
    
    protected $_joinConstraints = array();
    
    public function joinConstraint() {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->setTransaction($this->_sourceManager->getTransaction())
            ->beginJoinConstraint($this, IJoinQuery::INNER);
    }
    
    public function leftJoinConstraint() {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->setTransaction($this->_sourceManager->getTransaction())
            ->beginJoinConstraint($this, IJoinQuery::LEFT);
    }
    
    public function rightJoinConstraint() {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->setTransaction($this->_sourceManager->getTransaction())
            ->beginJoinConstraint($this, IJoinQuery::RIGHT);
    }
    
    public function addJoinConstraint(IJoinConstraintQuery $join) {
        if(!$this->_source->getAdapter()->supportsQueryType($join->getQueryType())) {
            throw new LogicException(
                'Query adapter '.$this->_source->getAdapter()->getQuerySourceDisplayName().' does not support joins'
            );
        }
        
        $this->_joinConstraints[$join->getSourceAlias()] = $join;
        return $this;
    }
    
    public function getJoins() {
        return $this->_joinConstraints;
    }
    
    public function clearJoins() {
        foreach($this->_joinConstraints as $sourceAlias => $join) {
            $this->_sourceManager->removeSource($sourceAlias);
        }
        
        $this->_joinConstraints = array();
        return $this;
    }
}



trait TQuery_JoinClauseFactoryBase {
    
    protected $_joinClauseList;
    
    public function beginOnClause() {
        return new opal\query\clause\JoinList($this);
    }

    public function beginOrOnClause() {
        return new opal\query\clause\JoinList($this, true);
    }

    
    public function addJoinClause(opal\query\IJoinClauseProvider $clause=null) {
        $this->getJoinClauseList()->addJoinClause($clause);
        return $this;
    }

    public function getJoinClauseList() {
        if(!$this->_joinClauseList) {
            $this->_joinClauseList = new opal\query\clause\JoinList($this);
        }
        
        return $this->_joinClauseList;
    }
    
    public function hasJoinClauses() {
        return !empty($this->_joinClauseList) 
            && !$this->_joinClauseList->isEmpty();
    }
    
    public function clearJoinClauses() {
        if($this->_joinClauseList) {
            $this->_joinClauseList->clearJoinClauses();
        }
        
        return $this;
    }
    
    public function getNonLocalFieldReferences() {
        return $this->_joinClauseList->getNonLocalFieldReferences();
    }
    
    public function referencesSourceAliases(array $sourceAliases) {
        if($this->_joinClauseList) {
            return $this->_joinClauseList->referencesSourceAliases($sourceAliases);
        }
        
        return false;
    }
}

trait TQuery_ParentAwareJoinClauseFactory {
    
    use TQuery_JoinClauseFactoryBase;
    
    public function on($localField, $operator, $foreignField) {
        $this->getJoinClauseList()->addJoinClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->_sourceManager->extrapolateIntrinsicField($this->_source, $localField),
                $operator,
                $this->_parent->getSourceManager()->extrapolateIntrinsicField(
                    $this->_parent->getSource(), 
                    $foreignField, 
                    $this->_source->getAlias()
                ),
                false
            )
        );
        
        return $this;
    }

    public function orOn($localField, $operator, $foreignField) {
        $this->getJoinClauseList()->addJoinClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->_sourceManager->extrapolateIntrinsicField($this->_source, $localField),
                $operator,
                $this->_parent->getSourceManager()->extrapolateIntrinsicField(
                    $this->_parent->getSource(), 
                    $foreignField, 
                    $this->_source->getAlias()
                ),
                true
            )
        );
        
        return $this;
    }
}



/*************************
 * Attachments
 */
trait TQuery_Attachable {
    
    protected $_attachments = array();
    
    public function attach() {
        return Initiator::factory($this->_sourceManager->getApplication())
            ->setTransaction($this->_sourceManager->getTransaction())
            ->beginAttach($this, func_get_args());
    }
    
    public function addAttachment($name, IAttachQuery $attachment) {
        if(!$this->_source->getAdapter()->supportsQueryType($attachment->getQueryType())) {
            throw new LogicException(
                'Query adapter '.$this->_source->getAdapter()->getQuerySourceDisplayName().
                ' does not support attachments'
            );
        }
        
        if(isset($this->_attach[$name])) {
            throw new RuntimeException(
                'An attachment has already been created with the name "'.$name.'"'
            );
        }
        
        $this->_attachments[$name] = $attachment;
        return $this;
    }
    
    public function getAttachments() {
        return $this->_attachments;
    }
    
    public function clearAttachments() {
        $this->_attachments = array();
        return $this;
    }
}



trait TQuery_Attachment {
    
    use TQuery_ParentAware;
    
    protected $_type;
    protected $_keyField;
    
    public static function typeIdToName($id) {
        switch($id) {
            case IAttachQuery::TYPE_ONE:
                return 'ONE';
                
            case IAttachQuery::TYPE_MANY:
                return 'MANY';
                
            case IAttachQuery::TYPE_LIST:
                return 'LIST';
        }
    }
    
    public function getType() {
        return $this->_type;
    }
    
    
// Output
    public function asOne($name) {
        if($this->_joinClauseList->isEmpty()) {
            throw new LogicException(
                'No join clauses have been defined for attachment '.$name
            );
        }
        
        $this->_type = IAttachQuery::TYPE_ONE;
        return $this->_parent->addAttachment($name, $this);
    }
    
    public function asMany($name, $keyField=null) {
        if($this->_joinClauseList->isEmpty()) {
            throw new LogicException(
                'No join clauses have been defined for attachment '.$name
            );
        }
        
        if($keyField !== null) {
            $this->_keyField = $this->getSourceManager()->extrapolateDataField($this->_source, $keyField);
        }
        
        $this->_type = IAttachQuery::TYPE_MANY;
        return $this->_parent->addAttachment($name, $this);
    }
    
    public function getListKeyField() {
        return $this->_keyField;
    }
}


trait TQuery_AttachmentListExtension {
    
    protected $_valField;

    public function asList($name, $field1, $field2=null) {
        if($this->_joinClauseList->isEmpty()) {
            throw new LogicException(
                'No join clauses have been defined for attachment '.$name
            );
        }
        
        $manager = $this->getSourceManager();
        
        if($field2 !== null) {
            $this->_keyField = $manager->extrapolateDataField($this->_source, $field1);
            $this->_valField = $manager->extrapolateDataField($this->_source, $field2);
        } else {
            $this->_valField = $manager->extrapolateDataField($this->_source, $field1);
        }
        
        $this->_type = IAttachQuery::TYPE_LIST;
        return $this->_parent->addAttachment($name, $this);
    }
    
    public function getListValueField() {
        return $this->_valField;
    }
}


/***************************
 * Prerequisites
 */
trait TQuery_PrerequisiteClauseFactory {
    
    protected $_prerequisites = array();
    
    public function wherePrerequisite($field, $operator, $value) {
        $this->_source->testWhereClauseSupport();
        
        $this->addPrerequisite(
            opal\query\clause\Clause::factory(
                $this, 
                $this->_sourceManager->extrapolateIntrinsicField($this->_source, $field), 
                $operator, 
                $value, 
                false
            )
        );
        
        return $this;
    }
    
    public function whereBeginPrerequisite() {
        $this->_source->testWhereClauseSupport();
        return new opal\query\clause\WhereList($this, false, true);
    }
    
    public function addPrerequisite(opal\query\IClauseProvider $clause=null) {
        if($clause !== null) {
            $clause->isOr(false);
            $this->_prerequisites[] = $clause;
        }
        
        return $this;
    }
    
    public function getPrerequisites() {
        return $this->_prerequisites;
    }
    
    public function hasPrerequisites() {
        return !empty($this->_prerequisites);
    }
    
    public function clearPrerequisites() {
        $this->_prerequisites = array();
        return $this;
    }
}





/****************************
 * Where clause
 */
trait TQuery_WhereClauseFactory {
    
    protected $_whereClauseList;
    
    public function where($field, $operator, $value) {
        $this->_source->testWhereClauseSupport();
        
        $this->_getWhereClauseList()->addWhereClause(
            opal\query\clause\Clause::factory(
                $this, 
                $this->_sourceManager->extrapolateIntrinsicField($this->_source, $field), 
                $operator, 
                $value, 
                false
            )
        );
        
        return $this;
    }
    
    public function orWhere($field, $operator, $value) {
        $this->_source->testWhereClauseSupport();
        
        $this->_getWhereClauseList()->addWhereClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->_sourceManager->extrapolateIntrinsicField($this->_source, $field), 
                $operator, 
                $value, 
                true
            )
        );
        
        return $this;
    }


    public function beginWhereClause() {
        $this->_source->testWhereClauseSupport();
        return new opal\query\clause\WhereList($this);
    }
    
    public function beginOrWhereClause() {
        $this->_source->testWhereClauseSupport();
        return new opal\query\clause\WhereList($this, true);
    }
    
    
    public function addWhereClause(opal\query\IWhereClauseProvider $clause=null) {
        $this->_source->testWhereClauseSupport();
        $this->_getWhereClauseList()->addWhereClause($clause);
        return $this;
    }
    
    public function getWhereClauseList() {
        return $this->_getWhereClauseList();
    }
    
    private function _getWhereClauseList() {
        if(!$this->_whereClauseList) {
            $this->_whereClauseList = new opal\query\clause\WhereList($this);
        }
        
        return $this->_whereClauseList;
    }
    
    public function hasWhereClauses() {
        return !empty($this->_whereClauseList) 
            && !$this->_whereClauseList->isEmpty();
    }
    
    public function clearWhereClauses() {
        if($this->_whereClauseList) {
            $this->_whereClauseList->clearWhereClauses();
        }
        
        return $this;
    }
}


trait TQuery_PrerequisiteAwareWhereClauseFactory {
    
    use TQuery_WhereClauseFactory;
    
    public function getWhereClauseList() {
        $this->_getWhereClauseList();
        
        if(empty($this->_prerequisites)) {
            return $this->_whereClauseList;
        }
        
        $output = new opal\query\clause\WhereList($this, false, true);
        
        foreach($this->_prerequisites as $clause) {
            $output->_addClause($clause);
        }
        
        if(!empty($this->_whereClauseList) && !$this->_whereClauseList->isEmpty()) {
            $output->_addClause($this->_whereClauseList);
        }
        
        return $output;
    }
    
    public function hasWhereClauses() {
        return !empty($this->_prerequisites)
            || (!empty($this->_whereClauseList) && !$this->_whereClauseList->isEmpty());
    }
}






/**************************
 * Groups
 */
trait TQuery_Groupable {
    
    protected $_groups = array();
    
    public function groupBy($field1) {
        $this->_source->testGroupDirectiveSupport();
        
        foreach(func_get_args() as $field) {
            $this->_groups[] = $this->_sourceManager->extrapolateIntrinsicField($this->_source, $field);
        }
        
        return $this;
    }
    
    public function getGroupFields() {
        return $this->_groups;
    }
    
    public function clearGroupFields() {
        $this->_groups = array();
        return $this;
    }
}





/**************************
 * Having
 */
trait TQuery_HavingClauseFactory {
    
    protected $_havingClauseList;
    
    public function having($field, $operator, $value) {
        $this->_source->testAggregateClauseSupport();
        
        $this->getHavingClauseList()->addHavingClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->_sourceManager->extrapolateAggregateField($this->_source, $field), 
                $operator, 
                $value, 
                false
            )
        );
        
        return $this;
    }
    
    public function orHaving($field, $operator, $value) {
        $this->_source->testAggregateClauseSupport();
        
        $this->getHavingClauseList()->addHavingClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->_sourceManager->extrapolateAggregateField($this->_source, $field), 
                $operator, 
                $value, 
                true
            )
        );
        
        return $this;
    }
    
    public function beginHavingClause() {
        $this->_source->testAggregateClauseSupport();
        return new opal\query\clause\HavingList($this);
    }
    
    public function beginOrHavingClause() {
        $this->_source->testAggregateClauseSupport();
        return new opal\query\clause\HavingList($this, true);
    }
    
    
    public function addHavingClause(opal\query\IHavingClauseProvider $clause=null) {
        $this->_source->testAggregateClauseSupport();
        $this->getHavingClauseList()->addHavingClause($clause);
        return $this;
    }
    
    public function getHavingClauseList() {
        if(!$this->_havingClauseList) {
            $this->_havingClauseList = new opal\query\clause\HavingList($this);
        }
        
        return $this->_havingClauseList;
    }
    
    public function hasHavingClauses() {
        return !empty($this->_havingClauseList) 
            && !$this->_havingClauseList->isEmpty();
    }
    
    public function clearHavingClauses() {
        if($this->_havingClauseList) {
            $this->_havingClauseList->clearHavingClauses();
        }
        
        return $this;
    }
}





/**************************
 * Order
 */
trait TQuery_Orderable {
    
    protected $_order = array();
    
    public function orderBy($field1) {
        $this->_source->testOrderDirectiveSupport();
        
        foreach(func_get_args() as $field) {
            $parts = explode(' ', $field);
            
            $directive = new OrderDirective(
                $this->_sourceManager->extrapolateField($this->_source, array_shift($parts)), 
                array_shift($parts)
            );
            
            $this->_order[] = $directive;
        }
        
        return $this;
    }
    
    public function setOrderDirectives(array $directives) {
        $this->_order = $directives;
        return $this;
    }
    
    public function getOrderDirectives() {
        return $this->_order;
    }
    
    public function clearOrderDirectives() {
        $this->_order = array();
        return $this;
    }
}





/*************************
 * Limit
 */
trait TQuery_Limitable {
    
    protected $_limit;
    protected $_maxLimit;
    
    public function limit($limit) {
        $this->_source->testLimitDirectiveSupport();
        
        if($limit) {
            $limit = (int)$limit;
            
            if($limit <= 0) {
                $limit = null;
            }
        } else {
            $limit = null;
        }
        
        $this->_limit = $limit;
        
        if($this->_maxLimit !== null && $this->_limit > $this->_maxLimit) {
            $this->_limit = $this->_maxLimit;
        }
        
        return $this;
    }
    
    public function getLimit() {
        return $this->_limit;
    }
    
    public function clearLimit() {
        $this->_limit = null;
        return $this;
    }
    
    public function hasLimit() {
        return $this->_limit !== null;
    }
}





/************************
 * Offset
 */
trait TQuery_Offsettable {
    
    protected $_offset;
    
    public function offset($offset) {
        $this->_source->testOffsetDirectiveSupport();
        
        if(!$offset) {
            $offset = null;
        }
        
        $this->_offset = $offset;
        return $this;
    }
    
    public function getOffset() {
        return $this->_offset;
    }
    
    public function clearOffset() {
        $this->_offset = null;
        return $this;
    }
    
    public function hasOffset() {
        return $this->_offset !== null;
    }
}





/*****************************
 * Populate
 */
trait TQuery_Populatable {
    
    public function populate($field) {
        core\stub($field);
    }
}





/*************************
 * Paginator
 */
trait TQuery_Pageable {
    
    protected $_paginator;
    
    public function paginate() {
        return new Paginator($this);
    }
    
    public function setPaginator(core\collection\IPaginator $paginator) {
        $this->_paginator = $paginator;
        return $this;
    }
    
    public function getPaginator() {
        if(!$this->_paginator) {
            $this->_paginator = $this->paginate()
                ->setDefaultLimit($this->_limit)
                ->setDefaultOffset($this->_offset);
        }
        
        return $this->_paginator;
    }
}






/**************************
 * Read
 */
trait TQuery_Read {
    
    public function getIterator() {
        $data = $this->_fetchSourceData();
        
        if(is_array($data)) {
            $data = new \ArrayIterator($data);
        }
        
        return $data;
    }
    
    public function toArray($keyField=null) {
        $data = $this->_fetchSourceData($keyField);
        
        if($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }
        
        if(!is_array($data)) {
            throw new UnexpectedValueException(
                'Source did not return a result that could be converted to an array'
            );
        }
        
        return $data;
    }
    
    public function toRow() {
        $limit = $this->_limit;
        $this->_limit = 1;
        $data = $this->toArray();
        $this->_limit = $limit;
        
        return array_shift($data);
    }
    
    public function getRawResult() {
        return $this->_fetchSourceData();
    }
    
    abstract protected function _fetchSourceData($keyField=null);
}







/**************************
 * Insert data
 */
trait TQuery_DataInsert {
    
    protected $_row;
    
    public function setRow($row) {
        if($row instanceof opal\query\IDataRowProvider) {
            $row = $row->toDataRowArray();
        } else if($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        } else if(!is_array($row)) {
            throw new InvalidArgumentException(
                'Insert data must be convertible to an array'
            );
        }
        
        if(empty($row)) {
            throw new InvalidArgumentException(
                'Insert data must contain at least one field'
            );
        }
        
        $this->_row = $row;
        return $this;
    }
    
    public function getRow() {
        return $this->_row;
    }
}



trait TQuery_BatchDataInsert {
    
    protected $_rows = array();
    protected $_fields = array();
    protected $_flushThreshold = 500;
    protected $_inserted = 0;
    
    public function addRows($rows) {
        if($rows instanceof core\IArrayProvider) {
            $rows = $rows->toArray();
        } else if(!is_array($rows)) {
            throw new InvalidArgumentException(
                'Batch insert data must be convertible to an array'
            );
        }
        
        foreach($rows as $row) {
            $this->addRow($row);
        }
        
        return $this;
    }
    
    public function addRow($row) {
        $row = $this->_normalizeRow($row);
        
        foreach($row as $field => $value) {
            $this->_fields[$field] = true;
        }
        
        $this->_rows[] = $row;
        
        if($this->_flushThreshold > 0
        && count($this->_rows) >= $this->_flushThreshold) {
            $this->execute();
        }
        
        return $this;
    }
    
    protected function _normalizeRow($row) {
        if($row instanceof opal\query\IDataRowProvider) {
            $row = $row->toDataRowArray();
        } else if($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        } else if(!is_array($row)) {
            throw new InvalidArgumentException(
                'Insert data must be convertible to an array'
            );
        }
        
        if(empty($row)) {
            throw new InvalidArgumentException(
                'Insert data must contain at least one field'
            );
        }
       
        return $row;
    }
    
    
    public function getRows() {
        return $this->_rows;
    }
    
    public function clearRows() {
        $this->_rows = array();
        return $this;
    }
    
    public function getFields() {
        return array_keys($this->_fields);
    }
    

// Count    
    public function countPending() {
        return count($this->_rows);
    }
    
    public function countInserted() {
        return $this->_inserted;
    }
    
    public function countTotal() {
        return $this->countPending() + $this->countInserted();
    }
    
// Flush threshold
    public function setFlushThreshold($flush) {
        $this->_flushThreshold = (int)$flush;
        return $this;
    }
    
    public function getFlushThreshold() {
        return $this->_flushThreshold;
    }
}






/****************************
 * Update data
 */
trait TQuery_DataUpdate {
    
    protected $_valueMap = array();
    
    public function set($key, $value=null) {
        if(is_array($key)) {
            $values = $key;
        } else {
            $values = array($key => $value);
        }
        
        $this->_valueMap = array_merge($this->_valueMap, $values);
    }
    
    public function setExpression($field, $expression) {
        core\stub($field, $expression);
    }
    
    public function getValueMap() {
        return $this->_valueMap;
    }
}









/**************************
 * Entry point
 */
trait TQuery_EntryPoint {
    
    public function select($field1=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginSelect(func_get_args());
    }
    
    public function fetch() {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginFetch();
    }
    
    public function insert($row) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginInsert($row);
    }
    
    public function batchInsert($rows=array()) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginBatchInsert($rows);
    }
    
    public function replace($row) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginReplace($row);
    }
    
    public function batchReplace($rows=array()) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginBatchReplace($rows);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginUpdate($valueMap);
    }
    
    public function delete() {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginDelete();
    }
    
    public function begin() {
        return new Transaction($this->_getEntryPointApplication());
    }
    
    private function _getEntryPointApplication() {
        if($this instanceof core\IApplicationAware) {
            return $this->getApplication();
        } else {
            return df\Launchpad::getActiveApplication();
        }
    }
}




/*******************************
 * Implicit source entry point
 */
trait TQuery_ImplicitSourceEntryPoint {
    
    public function select($field1=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginSelect(func_get_args())
            ->from($this);
    }
    
    public function fetch() {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginFetch()
            ->from($this);
    }
    
    public function insert($row) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginInsert($row)
            ->into($this);
    }
    
    public function batchInsert($rows=array()) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginBatchInsert($rows)
            ->into($this);
    }
    
    public function replace($row) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginReplace($row)
            ->in($this);
    }
    
    public function batchReplace($rows=array()) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginBatchReplace($rows)
            ->in($this);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginUpdate($valueMap)
            ->in($this);
    }
    
    public function delete() {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginDelete()
            ->from($this);
    }
    
    public function begin() {
        return new ImplicitSourceTransaction($this->_getEntryPointApplication(), $this);
    }
    
    private function _getEntryPointApplication() {
        if($this instanceof core\IApplicationAware) {
            return $this->getApplication();
        } else {
            return df\Launchpad::getActiveApplication();
        }
    }
}