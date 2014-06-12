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
use df\user;

class Rdbms implements 
    axis\ISchemaProviderAdapter, 
    axis\IConnectionProxyAdapter,
    axis\IIntrospectableAdapter,
    opal\query\IAdapter,
    core\IDumpable {
    
    use user\TAccessLock;
    
    protected $_connection;
    protected $_querySourceAdapter;    
    protected $_unit;
    protected $_clusterId;
    
    public function __construct(axis\IAdapterBasedStorageUnit $unit) {
        $this->_unit = $unit;
        $this->_clusterId = $unit->getModel()->getClusterId();
    }
    
    public function getDisplayName() {
        return 'Rdbms';
    }

    public function getUnit() {
        return $this->_unit;
    }

    public function getConnection() {
        if(!$this->_connection) {
            $settings = $this->_unit->getUnitSettings();
            $dsn = opal\rdbms\Dsn::factory($settings['dsn']);

            if($this->_clusterId) {
                $dsn->setDatabaseSuffix('_'.$this->_clusterId);
            }

            $this->_connection = opal\rdbms\adapter\Base::factory($dsn, true);
        }
        
        return $this->_connection;
    }

    public function getConnectionDisplayName() {
        return $this->getConnection()->getDsn()->getDisplayString();
    }
    
// Query source
    public function getQuerySourceId() {
        return 'axis://Unit:"'.$this->_unit->getUnitId().'"';
    }
    
    public function getQuerySourceDisplayName() {
        return $this->_unit->getUnitId();
    }
    
    public function getQuerySourceAdapterHash() {
        return $this->getConnection()->getDsnHash();
    }
    
    public function getQuerySourceAdapter() {
        if(!$this->_querySourceAdapter) {
            $this->_querySourceAdapter = $this->getConnection()->getTable($this->_unit->getStorageBackendName());
        }
        
        return $this->_querySourceAdapter;
    }
    
    public function getDelegateQueryAdapter() {
        return $this->getQuerySourceAdapter()->getDelegateQueryAdapter();
    }

    public function getClusterId() {
        return $this->_unit->getClusterId();
    }
    
    public function supportsQueryType($type) {
        return $this->getQuerySourceAdapter()->supportsQueryType($type);
    }
    
    public function supportsQueryFeature($feature) {
        return $this->getQuerySourceAdapter()->supportsQueryFeature($feature);
    }
    
    
    
// Query proxy
    public function executeSelectQuery(opal\query\ISelectQuery $query) {
        return $this->getQuerySourceAdapter()->executeSelectQuery($query);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        return $this->getQuerySourceAdapter()->countSelectQuery($query);
    }

    public function executeUnionQuery(opal\query\IUnionQuery $query) {
        return $this->getQuerySourceAdapter()->executeUnionQuery($query);
    }

    public function countUnionQuery(opal\query\IUnionQuery $query) {
        return $this->getQuerySourceAdapter()->countUnionQuery($query);
    }
    
    public function executeFetchQuery(opal\query\IFetchQuery $query) {
        return $this->getQuerySourceAdapter()->executeFetchQuery($query);
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

    public function newPartial(array $values=null) {
        return $this->_unit->newPartial($values);
    }
    
    
    
    
// Create
    public function createStorageFromSchema(axis\schema\ISchema $axisSchema) {
        $adapter = $this->getConnection();
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
        if($e instanceof opal\rdbms\TableNotFoundException) {
            if(strtolower($e->table) == strtolower($this->_unit->getStorageBackendName())) {
                $this->ensureStorageConsistency();
                return true;
            }
        }
        
        return false;
    }

    public function ensureStorageConsistency() {
        $model = $this->_unit->getModel();
        $defUnit = $model->getSchemaDefinitionUnit();

        $idList = $defUnit->fetchStoredUnitList();
        $tableList = $this->getConnection()->getDatabase()->getTableList();
        $update = [];
        $remove = [];

        $unitId = $this->_unit->getUnitId();

        if(!in_array($unitId, $idList)) {
            $idList[] = $unitId;
        }

        foreach($idList as $unitId) {
            try {
                $unit = $model->loadUnitFromId($unitId);
            } catch(axis\RuntimeException $e) {
                $remove[] = $unitId;
                continue;
            }

            $backendName = $unit->getStorageBackendName();

            if(!in_array($backendName, $tableList)) {
                $update[$backendName] = $unit;
            }
        }

        if(!empty($remove)) {
            $defUnit->clearCache();
            
            foreach($remove as $unitId) {
                $defUnit->removeId($unitId);
            }
        }

        if(!empty($update)) {
            $defUnit->clearCache();

            foreach($update as $name => $unit) {
                $defUnit->remove($unit);
                $defUnit->fetchFor($unit);
            }
        }

        return $this;
    }



// Introspection
    public function getStorageList() {
        return $this->getConnection()->getDatabase()->getTableList();
    }

    public function describeStorage($name=null) {
        if($name === null) {
            $name = $this->_unit->getStorageBackendName();
        }

        $table = $this->getConnection()->getTable($name);
        $stats = $table->getStats();
        
        return new axis\introspector\StorageDescriber(
            $name,
            $this->getConnection()->getAdapterName(),
            $stats->getRowCount(),
            $stats->getSize(),
            $stats->getIndexSize(),
            $stats->getCreationDate()
        );
    }

    public function destroyDescribedStorage($name) {
        if($name instanceof axis\introspector\IStorageDescriber) {
            $name = $name->getName();
        }

        $this->getConnection()->getTable($name)->drop();
        return $this;
    }

    
// Access
    public function getAccessLockDomain() {
        return $this->_unit->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action=null) {
        return $this->_unit->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null) {
        return $this->_unit->getDefaultAccess($action);
    }

    public function getAccessLockId() {
        return $this->_unit->getAccessLockId();
    }

    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->getDisplayName(),
            'unit' => $this->_unit->getUnitId() 
        ];
    }
}
