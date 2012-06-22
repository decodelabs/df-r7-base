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

class Rdbms implements axis\ISchemaDefinitionStorageAdapter {
    
    protected $_table;
    protected $_unit;
    
    public function __construct(axis\IAdapterBasedStorageUnit $unit) {
        $this->_unit = $unit;
        
        $config = axis\ConnectionConfig::getInstance($this->_unit->getModel()->getApplication());
        $settings = $config->getSettingsFor($this->_unit);
        $rdbmsAdapter = opal\rdbms\adapter\Base::factory($settings['dsn']);
        $this->_table = $rdbmsAdapter->getTable($this->_unit->getStorageBackendName());
    }
    
    public function fetchFor(axis\ISchemaBasedStorageUnit $unit) {
        return $this->_table->select('schema')
            ->Where('unitId', '=', $unit->getUnitId())
            ->toValue();
    }
    
    public function getTimestampFor(axis\ISchemaBasedStorageUnit $unit) {
        return $this->_table->select('timestamp')
            ->where('unitId', '=', $unit->getUnitId())
            ->toValue('timestamp');
    }
    
    public function insert(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version) {
        $this->_table->insert([
                'unitId' => $unit->getUnitId(),
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
                'version' => $version
            ])
            ->where('unitId', '=', $unit->getUnitId())
            ->execute();
            
        return $this;
    }
    
    public function remove(axis\ISchemaBasedStorageUnit $unit) {
        $this->_table->delete()
            ->where('unitId', '=', $unit->getUnitId())
            ->execute();
            
        return $this;
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
        $bridge = new axis\schema\bridge\Rdbms($this->_unit, $this->_table->getAdapter(), $axisSchema);
        $opalSchema = $bridge->updateTargetSchema();
        $this->_table->create($opalSchema);
        
        return $this;
    }
    
    public function destroyStorage() {
        $this->_table->drop();
        
        return $this;
    }
}
