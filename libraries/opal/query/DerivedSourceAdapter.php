<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;
use df\user;

class DerivedSourceAdapter implements IDerivedSourceAdapter {
    
    use user\TAccessLock;

    protected $_query;
    protected $_adapter;

    public function __construct(IDerivableQuery $query) {
        $this->_query = $query;
        $this->_adapter = $query->getDerivationSourceAdapter();
    }

    public function getDerivationQuery() {
        return $this->_query;
    }

    public function getDerivationSource() {
        return $this->_query->getSource();
    }

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

    
    public function handleQueryException(IQuery $query, \Exception $e) {
        return $this->_adapter->handleQueryException($query, $e);
    }

    public function ensureStorageConsistency() {
        return $this->_adapter->ensureStorageConsistency();
    }

    
    public function executeSelectQuery(ISelectQuery $query) {
        return $this->_adapter->executeSelectQuery($query);
    }

    public function countSelectQuery(ISelectQuery $query) {
        return $this->_adapter->countSelectQuery($query);
    }

    public function executeUnionQuery(IUnionQuery $query) {
        return $this->_adapter->executeUnionQuery($query);
    }

    public function countUnionQuery(IUnionQuery $query) {
        return $this->_adapter->countUnionQuery($query);
    }

    public function executeFetchQuery(IFetchQuery $query) {
        return $this->_adapter->executeFetchQuery($query);
    }

    public function countFetchQuery(IFetchQuery $query) {
        return $this->_adapter->countFetchQuery($query);
    }

    public function executeInsertQuery(IInsertQuery $query) {
        return $this->_adapter->executeInsertQuery($query);
    }

    public function executeBatchInsertQuery(IBatchInsertQuery $query) {
        return $this->_adapter->executeBatchInsertQuery($query);
    }

    public function executeReplaceQuery(IReplaceQuery $query) {
        return $this->_adapter->executeReplaceQuery($query);
    }

    public function executeBatchReplaceQuery(IBatchReplaceQuery $query) {
        return $this->_adapter->executeBatchReplaceQuery($query);
    }

    public function executeUpdateQuery(IUpdateQuery $query) {
        return $this->_adapter->executeUpdateQuery($query);
    }

    public function executeDeleteQuery(IDeleteQuery $query) {
        return $this->_adapter->executeDeleteQuery($query);
    }

    
    public function fetchRemoteJoinData(IJoinQuery $join, array $rows) {
        return $this->_adapter->fetchRemoteJoinData($join, $rows);
    }

    public function fetchAttachmentData(IAttachQuery $attachment, array $rows) {
        return $this->_adapter->fetchAttachmentData($attachment, $rows);
    }

    
    public function beginQueryTransaction() {
        return $this->_adapter->beginQueryTransaction();
    }

    public function commitQueryTransaction() {
        return $this->_adapter->commitQueryTransaction();
    }

    public function rollbackQueryTransaction() {
        return $this->_adapter->rollbackQueryTransaction();
    }

    
    public function newRecord(array $values=null) {
        return $this->_adapter->newRecord($values);
    }

    public function newPartial(array $values=null) {
        return $this->_adapter->newPartial($values);
    }


// Access
    public function getAccessLockDomain() {
        return $this->_adapter->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action=null) {
        return $this->_adapter->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null) {
        return $this->_adapter->getDefaultAccess($action);
    }

    public function getAccessLockId() {
        return $this->_adapter->getAccessLockId();
    }
}