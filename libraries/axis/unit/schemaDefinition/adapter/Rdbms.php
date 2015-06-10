<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\schemaDefinition\adapter;

use df;
use df\core;
use df\axis;
use df\opal;

class Rdbms implements 
    axis\ISchemaDefinitionStorageAdapter,
    axis\IConnectionProxyAdapter {
    
    protected $_table;
    protected $_unit;
    
    public function __construct(axis\IAdapterBasedStorageUnit $unit) {
        $this->_unit = $unit;
        
        $config = axis\ConnectionConfig::getInstance();
        $settings = $config->getSettingsFor($this->_unit);
        $rdbmsAdapter = opal\rdbms\adapter\Base::factory($settings['dsn']);
        $this->_table = $rdbmsAdapter->getTable($this->_unit->getStorageBackendName());
    }

    public function getDisplayName() {
        return 'Rdbms';
    }

    public function getUnit() {
        return $this->_unit;
    }

    public function getStorageGroupName() {
        $output = $this->_table->getDatabaseName();

        if(substr($output, -3) == '.db') {
            $output = substr($output, 0, -3);
        }

        return $output;
    }
    

// Connection
    public function getConnection() {
        return $this->_table->getAdapter();
    }

    public function getConnectionDisplayName() {
        return $this->getConnection()->getDsn()->getDisplayString();
    }

    public function getConnectionHash() {
        return $this->getConnection()->getDsnHash();
    }

// Definitions
    public function fetchFor(axis\ISchemaBasedStorageUnit $unit) {
        return $this->_table->select('schema')
            ->where('unitId', '=', $unit->getGlobalUnitId())
            ->toValue();
    }
    
    public function getTimestampFor(axis\ISchemaBasedStorageUnit $unit) {
        return $this->_table->select('timestamp')
            ->where('unitId', '=', $unit->getGlobalUnitId())
            ->toValue('timestamp');
    }
    
    public function insert(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version) {
        $this->_table->insert([
                'unitId' => $unit->getGlobalUnitId(),
                'storeName' => $unit->getStorageBackendName(),
                'version' => $version,
                'schema' => $jsonData
            ])
            ->execute();
            
        return $this;
    }
    
    public function update(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version) {
        $this->_table->update([
                'schema' => $jsonData,
                'version' => $version,
                'timestamp' => core\time\Date::factory('now')->toString()
            ])
            ->where('unitId', '=', $unit->getGlobalUnitId())
            ->execute();
            
        return $this;
    }
    
    public function remove(axis\ISchemaBasedStorageUnit $unit) {
        return $this->removeId($unit->getGlobalUnitId());
    }

    public function removeId($unitId) {
        $this->_table->delete()
            ->where('unitId', '=', $unitId)
            ->execute();
            
        return $this;
    }

    public function fetchStoredUnitList() {
        return $this->_table->select('unitId')
            ->orderBy('unitId ASC')
            ->toList('unitId');
    }

    public function fetchRawData() {
        return $this->_table->select()->toArray();
    }
    
    
    
    public function ensureStorage() {
        if($this->_table->exists()) {
            return false;
        }
        
        $schema = $this->_unit->getTransientUnitSchema();
        $this->createStorageFromSchema($schema);

        return true;        
    }


    public function createStorageFromSchema(axis\schema\ISchema $axisSchema) {
        $translator = new axis\schema\translator\Rdbms($this->_unit, $this->_table->getAdapter(), $axisSchema);
        $dbSchema = $translator->createFreshTargetSchema();

        return $this->_table->create($dbSchema);
    }

    public function updateStorageFromSchema(axis\schema\ISchema $axisSchema) {
        $translator = new axis\schema\translator\Rdbms($this->_unit, $this->_table->getAdapter(), $axisSchema, $this->_table->getSchema());
        $dbSchema = $translator->updateTargetSchema();

        if($dbSchema->hasChanged()) {
            return $this->_table->alter($dbSchema);
        }

        return $table;
    }
    
    public function destroyStorage() {
        $this->_table->drop();
        return $this;
    }

    public function storageExists() {
        return $this->_table->exists();
    }
}
