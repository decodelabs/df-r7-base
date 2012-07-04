<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\adapter;

use df;
use df\core;
use df\axis;
use df\opal;

class Rdbms implements 
    axis\ISchemaProviderAdapter, 
    opal\query\IAdapter,
    core\IDumpable {
    
    protected $_rdbmsAdapter;
    protected $_querySourceAdapter;    
    protected $_unit;
    
    public function __construct(axis\IAdapterBasedStorageUnit $unit) {
        $this->_unit = $unit;
    }
    
    
// Query source
    public function getQuerySourceId() {
        return 'axis://Unit:"'.$this->_unit->getUnitId().'"';
    }
    
    public function getQuerySourceDisplayName() {
        return $this->_unit->getUnitId();
    }
    
    public function getQuerySourceAdapterHash() {
        return $this->_getRdbmsAdapter()->getDsnHash();
    }
    
    public function getQuerySourceAdapter() {
        if(!$this->_querySourceAdapter) {
            $this->_querySourceAdapter = $this->_getRdbmsAdapter()->getTable($this->_unit->getStorageBackendName());
        }
        
        return $this->_querySourceAdapter;
    }
    
    protected function _getRdbmsAdapter() {
        if(!$this->_rdbmsAdapter) {
            $settings = $this->_unit->getUnitSettings();
            $this->_rdbmsAdapter = opal\rdbms\adapter\Base::factory($settings['dsn']);
        }
        
        return $this->_rdbmsAdapter;
    }
    
    public function getDelegateQueryAdapter() {
        return $this->getQuerySourceAdapter()->getDelegateQueryAdapter();
    }
    
    public function supportsQueryType($type) {
        return $this->getQuerySourceAdapter()->supportsQueryType($type);
    }
    
    public function supportsQueryFeature($feature) {
        return $this->getQuerySourceAdapter()->supportsQueryFeature($feature);
    }
    
    
    
// Query proxy
    public function executeSelectQuery(opal\query\ISelectQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null) {
        return $this->getQuerySourceAdapter()->executeSelectQuery($query, $keyField, $valField);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        return $this->getQuerySourceAdapter()->countSelectQuery($query);
    }
    
    public function executeFetchQuery(opal\query\IFetchQuery $query, opal\query\IField $keyField=null) {
        return $this->getQuerySourceAdapter()->executeFetchQuery($query, $keyField);
    }

    public function countFetchQuery(opal\query\IFetchQuery $query) {
        return $this->getQuerySourceAdapter()->countFetchQuery($query);
    }

    public function executeInsertQuery(opal\query\IInsertQuery $query) {
        return $this->getQuerySourceAdapter()->executeInsertQuery($query);
    }

    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        return $this->getQuerySourceAdapter()->executeBatchInsertQuery($query);
    }

    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        return $this->getQuerySourceAdapter()->executeReplaceQuery($query);
    }

    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        return $this->getQuerySourceAdapter()->executeBatchReplaceQuery($query);
    }

    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {
        return $this->getQuerySourceAdapter()->executeUpdateQuery($query);
    }

    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {
        return $this->getQuerySourceAdapter()->executeDeleteQuery($query);
    }
    
    
    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        return $this->getQuerySourceAdapter()->fetchRemoteJoinData($join, $rows);
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        return $this->getQuerySourceAdapter()->fetchAttachmentData($attachment, $rows);
    }

    
    

// Transactions
    public function beginQueryTransaction() {
        return $this->getQuerySourceAdapter()->beginQueryTransaction();
    }

    public function commitQueryTransaction() {
        return $this->getQuerySourceAdapter()->commitQueryTransaction();
    }

    public function rollbackQueryTransaction() {
        return $this->getQuerySourceAdapter()->rollbackQueryTransaction();
    }
    
    
// Record
    public function newRecord(array $values=null) {
        return $this->_unit->newRecord($values);
    }
    
    
    
    
// Create
    public function createStorageFromSchema(axis\schema\ISchema $axisSchema) {
        $adapter = $this->_getRdbmsAdapter();
        $bridge = new axis\schema\bridge\Rdbms($this->_unit, $adapter, $axisSchema);
        $dbSchema = $bridge->updateTargetSchema();
        
        try {
            return $adapter->createTable($dbSchema);
        } catch(opal\rdbms\TableConflictException $e) {
            // TODO: check db schema matches
            
            return $adapter->getTable($dbSchema->getName());
        }
    }
    
    public function destroyStorage() {
        $table = $this->getQuerySourceAdapter();
        $table->drop();
        
        return $this;
    }

    
// Query exceptions
    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        // Table not found
        if($e instanceof opal\rdbms\TableNotFoundException && $e->table == $this->_unit->getStorageBackendName()) {
            core\dump($e);
            $this->_unit->destroyStorage();
            $this->_unit->getUnitSchema();
            
            return true;
        }
        
        switch($query->getQueryType()) {
            // TODO: do something here :)
        }
        
        return false;
    }

    
    
// Dump
    public function getDumpProperties() {
        return array(
            'adapter' => get_class($this),
            'unit' => $this->_unit->getUnitId() 
        );
    }
}
