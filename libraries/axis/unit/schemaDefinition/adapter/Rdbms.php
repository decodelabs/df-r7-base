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

class Rdbms implements axis\IAdapter {
    
    protected $_rdbmsAdapter;
    protected $_unit;
    
    public function __construct(axis\IAdapterBasedStorageUnit $unit) {
        $this->_unit = $unit;
    }
    
    protected function _getRdbmsAdapter() {
        if(!$this->_rdbmsAdapter) {
            $config = axis\ConnectionConfig::getInstance($this->_unit->getModel()->getApplication());
            $settings = $config->getSettingsFor($this->_unit);
            $this->_rdbmsAdapter = opal\rdbms\adapter\Base::factory($settings['dsn']);
        }
        
        return $this->_rdbmsAdapter;
    }
    
    public function fetchFor(axis\ISchemaBasedStorageUnit $unit) {
        $adapter = $this->_getRdbmsAdapter();
        $table = $adapter->getTable($this->_unit->getStorageBackendName());
        
        return $table->select('schema')
            ->Where('unitId', '=', $unit->getUnitId())
            ->toValue();
    }
    
    public function getTimestampFor(axis\ISchemaBasedStorageUnit $unit) {
        $adapter = $this->_getRdbmsAdapter();
        $table = $adapter->getTable($this->_unit->getStorageBackendName());
        
        return $table->select('timestamp')
            ->where('unitId', '=', $unit->getUnitId())
            ->toValue('timestamp');
    }
    
    public function insert(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version) {
        $adapter = $this->_getRdbmsAdapter();
        $table = $adapter->getTable($this->_unit->getStorageBackendName());
        
        $table->insert([
                'unitId' => $unit->getUnitId(),
                'tableName' => $unit->getStorageBackendName(),
                'version' => $version,
                'schema' => $jsonData
            ])
            ->execute();
            
        return $this;
    }
    
    public function update(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version) {
        $adapter = $this->_getRdbmsAdapter();
        $table = $adapter->getTable($this->_unit->getStorageBackendName());
        
        $table->update([
                'schema' => $jsonData,
                'version' => $version
            ])
            ->where('unitId', '=', $unit->getUnitId())
            ->execute();
            
        return $this;
    }
    
    public function remove(axis\ISchemaBasedStorageUnit $unit) {
        $adapter = $this->_getRdbmsAdapter();
        $table = $adapter->getTable($this->_unit->getStorageBackendName());
        
        $table->delete()
            ->where('unitId', '=', $unit->getUnitId())
            ->execute();
            
        return $this;
    }
    
    
    public function ensureStorage() {
        $adapter = $this->_getRdbmsAdapter();
        $table = $adapter->getTable($this->_unit->getStorageBackendName());
        
        if($table->exists()) {
            return false;
        }
        
        $schema = new axis\schema\Base($this->_unit, $this->_unit->getStorageBackendName());
        
        $schema->addField('unitId', 'String', 64);
        $schema->addField('tableName', 'String', 128);
        $schema->addField('version', 'Integer', 1);
        $schema->addField('schema', 'BigBinary', 16);
        $schema->addField('timestamp', 'Timestamp');
        
        $schema->addPrimaryIndex('unitId');
        $schema->addIndex('timestamp');
        
        $bridge = new axis\schema\bridge\Rdbms($this->_unit, $adapter, $schema);
        $opalSchema = $bridge->updateTargetSchema();
        
        $adapter->createTable($opalSchema);
        return true;        
    }
}
