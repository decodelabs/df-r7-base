<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\search;

use df;
use df\core;
use df\axis;
use df\opal;

abstract class Base extends axis\Unit implements
    axis\IAdapterBasedStorageUnit,
    opal\query\IAdapter {
    
    protected $_adapter;
    
    public function __construct(axis\IModel $model, $unitName=null) {
        parent::__construct($model);
        $this->_adapter = self::loadAdapter($this);
    }
    
    public function getUnitType() {
        return 'search';
    }
    
    public function getUnitAdapter() {
        return $this->_adapter;
    }



    public function destroyStorage() {
        core\stub();
    }


// Query source
    public function getQuerySourceId() {
        return $this->_adapter->getQuerySourceId();
    }
    
    public function getQuerySourceAdapterHash() {
        return $this->_adapter->getQuerySourceAdapterHash();
    }
    
    public function getQuerySourceDisplayName() {
        return $this->_adapter->getQuerySourceDisplayName();
    }
    
    public function getDelegateQueryAdapter() {
        return $this->_adapter->getDelegateQueryAdapter();
    }
    
    public function supportsQueryType($type) {
        return $this->_adapter->supportsQueryType($type);
    }
    
    public function supportsQueryFeature($feature) {
        return $this->_adapter->supportsQueryFeature($feature);
    }
    
    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        return $this->_adapter->handleQueryException($query, $e);
    }
    
    
    
// Query proxy
    public function executeSelectQuery(opal\query\ISelectQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null) {
        return $this->_adapter->executeSelectQuery($query, $keyField, $valField);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        return $this->_adapter->countSelectQuery($query);
    }
    
    public function executeFetchQuery(opal\query\IFetchQuery $query, opal\query\IField $keyField=null) {
        return $this->_adapter->executeFetchQuery($query, $keyField);
    }

    public function countFetchQuery(opal\query\IFetchQuery $query) {
        return $this->_adapter->countFetchQuery($query);
    }

    public function executeInsertQuery(opal\query\IInsertQuery $query) {
        return $this->_adapter->executeInsertQuery($query);
    }

    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        return $this->_adapter->executeBatchInsertQuery($query);
    }

    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        return $this->_adapter->executeReplaceQuery($query);
    }

    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        return $this->_adapter->executeBatchReplaceQuery($query);
    }

    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {
        return $this->_adapter->executeUpdateQuery($query);
    }

    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {
        return $this->_adapter->executeDeleteQuery($query);
    }
    
    
    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        return $this->_adapter->fetchRemoteJoinData($join, $rows);
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        return $this->_adapter->fetchAttachmentData($attachment, $rows);
    }
    
    
    
// Query helpers
    public function dereferenceQuerySourceWildcard(opal\query\ISource $source) {
        return null;
    }
    
    public function extrapolateQuerySourceField(opal\query\ISource $source, $name, $alias=null, opal\schema\IField $field=null) {
        return null;
    }
    
    
    
// Clause helpers
    public function prepareQueryClauseValue(opal\query\IField $field, $value) {
        return $value;
    }
    
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        return null;
    }
            
            
    
// Value processors
    public function getQueryResultValueProcessors(array $fields=null) {
        return null;
    }        
    
    public function deflateInsertValues(array $row) {
        return $row;
    }
    
    public function normalizeInsertId($originalId, array $row) {
        return $originalId;
    }
    
    public function deflateBatchInsertValues(array $rows, array &$queryFields) {
        return $rows;
    }
    
    public function deflateReplaceValues(array $row) {
        return $row;
    }
    
    public function normalizeReplaceId($originalId, array $row) {
        return $originalId;
    }
    
    public function deflateBatchReplaceValues(array $rows, array &$queryFields) {
        return $rows;
    }
    
    public function deflateUpdateValues(array $values) {
        return $values;
    }
    
    
    
            
// Transactions
    public function beginQueryTransaction() {
        return $this->_adapter->beginQueryTransaction();
    }

    public function commitQueryTransaction() {
        return $this->_adapter->commitQueryTransaction();
    }

    public function rollbackQueryTransaction() {
        return $this->_adapter->rollbackQueryTransaction();
    }
    
    
// Record
    public function newRecord(array $values=null) {
        return new opal\query\record\Base($this, $values);
    }
    
    public function getRecordPrimaryFieldNames() {
        return null;
    }
    
    public function getRecordFieldNames() {
        return null;
    }
    
    

// Entry point
    public function fetchByPrimary($keys) {
        core\stub($keys);
    }
}
