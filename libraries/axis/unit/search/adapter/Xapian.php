<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\search\adapter;

use df;
use df\core;
use df\axis;
use df\opal;

class Xapian implements
    axis\IAdapter,
    opal\query\IAdapter {
    
    protected $_unit;
    
    public function __construct(axis\IAdapterBasedStorageUnit $unit) {
        $this->_unit = $unit;
    }
    
    
// Query source
    public function getQuerySourceId() {
        return 'axis://Unit:"'.$this->_unit->getUnitId().'"';
    }
    
    public function getQuerySourceAdapterHash() {
        core\stub();
    }
    
    public function getQuerySourceDisplayName() {
        return $this->_unit->getUnitId();
    }
    
    public function getDelegateQueryAdapter() {
        return $this;
    }
    
    public function supportsQueryType($type) {
        core\stub($type);
    }
    
    public function supportsQueryFeature($feature) {
        core\stub($feature);
    }
    
    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        core\stub($query, $e);
    }
    
    
    
// Query handlers
    public function executeSelectQuery(opal\query\ISelectQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null) {
        core\stub();
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        core\stub();
    }
    
    public function executeFetchQuery(opal\query\IFetchQuery $query, opal\query\IField $keyField=null) {
        core\stub();
    }

    public function countFetchQuery(opal\query\IFetchQuery $query) {
        core\stub();
    }

    public function executeInsertQuery(opal\query\IInsertQuery $query) {
        core\stub();
    }

    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        core\stub();
    }

    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        core\stub();
    }

    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        core\stub();
    }

    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {
        core\stub();
    }

    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {
        core\stub();
    }
    
    
    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        core\stub();
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        core\stub();
    }
    
    
// Transaction
    public function beginQueryTransaction() {
        core\stub();
    }
    
    public function commitQueryTransaction() {
        core\stub();
    }
    
    public function rollbackQueryTransaction() {
        core\stub();
    }
    
// Record
    public function newRecord(array $values=null) {
        return $this->_unit->newRecord($values);
    }
}
