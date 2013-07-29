<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\schemaDefinition;

use df;
use df\core;
use df\axis;
use df\opal;

final class Virtual implements axis\ISchemaDefinitionStorageUnit, axis\ISchemaBasedStorageUnit, axis\IVirtualUnit {

    use axis\TUnit;
    use axis\TAdapterBasedStorageUnit;

    public static function loadVirtual(axis\IModel $model, array $args) {
        return new self($model);
    }    
    
    public function __construct(axis\IModel $model, $unitName=null) {
        $this->_model = $model;
        $this->_loadAdapter();
    }
    
    public function getUnitType() {
        return 'schemaDefinition';
    }
    
    public function getStorageBackendName() {
        $output = 'axis_schemas';

        if($this->_shouldPrefixNames()) {
            $output = $this->_model->getApplication()->getUniquePrefix().'_'.$output;
        }

        return $output;
    }
    
    public function fetchFor(axis\ISchemaBasedStorageUnit $unit, $transient=false) {
        $cache = axis\schema\Cache::getInstance($this->_model->getApplication());
        $schema = $cache->get($unit->getUnitId());
        
        if($schema !== null && !$schema instanceof axis\schema\ISchema) {
            $schema = null;
            $cache->clear();
        }
        
        $setCache = false;
        $schemaJson = null;
        
        if(!$schema) {
            try {
                $schemaJson = $this->_adapter->fetchFor($unit);
            } catch(\Exception $e) {
                if(!$this->_adapter->ensureStorage()) {
                    throw $e;
                }
            }
        }
        
        if(!$schema && $schemaJson) {
            $schema = axis\schema\Base::fromJson($unit, $schemaJson);
            $setCache = true;
        }
        
        
        if(!$schema) {
            $schema = $unit->buildInitialSchema();
            $unit->updateUnitSchema($schema);
            
            if(!$transient) {
                $unit->validateUnitSchema($schema);
                $unit->createStorageFromSchema($schema);
                
                $schema->acceptChanges();
                $this->store($unit, $schema);
                
                $setCache = true;
            }
        }
        
        if($setCache && !$transient) {    
            $cache->set($unit->getUnitId(), $schema);
        }
        
        return $schema;
    }

    
    public function store(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $currentTimestamp = $this->_adapter->getTimestampFor($unit);
        $jsonData = $schema->toJson();
        
        try {
            if($currentTimestamp === null) {
                $this->_adapter->insert($unit, $jsonData, $schema->getVersion());
            } else {
                $this->_adapter->update($unit, $jsonData, $schema->getVersion());
            }
        } catch(\Exception $e) {
            if(!$this->_adapter->ensureStorage()) {
                throw $e;
            }
            
            if($currentTimestamp === null) {
                $this->_adapter->insert($unit, $jsonData, $schema->getVersion());
            } else {
                $this->_adapter->update($unit, $jsonData, $schema->getVersion());
            }
        }
        
        $this->clearCache($unit);
        
        return $this;
    }
    
    public function remove(axis\ISchemaBasedStorageUnit $unit) {
        try {
            $this->_adapter->remove($unit);
        } catch(\Exception $e) {
            if(!$this->_adapter->ensureStorage()) {
                throw $e;
            }
            
            $this->_adapter->remove($unit);
        }
        
        $this->clearCache($unit);
        
        return $this;
    }
    
    public function clearUnitSchemaCache() {
        return $this->clearCache($this);
    }
    
    public function clearCache(axis\ISchemaBasedStorageUnit $unit=null) {
        $cache = axis\schema\Cache::getInstance($this->_model->getApplication());
        
        if($unit) {
            $cache->remove($unit->getUnitId());
        } else {
            $cache->clear();
        }
        
        return $this;
    }
    
    
    public function getUnitSchema() {
        $schema = $this->getTransientUnitSchema();
        $schema->acceptChanges();

        return $schema;
    }
    
    public function getTransientUnitSchema() {
        $schema = new axis\schema\Base($this, $this->getStorageBackendName());
        
        $schema->addField('unitId', 'String', 64);
        $schema->addField('storeName', 'String', 128);
        $schema->addField('version', 'Integer', 1);
        $schema->addField('schema', 'BigBinary', 16);
        $schema->addField('timestamp', 'Timestamp');
        
        $schema->addPrimaryIndex('unitId');
        $schema->addIndex('timestamp');
        
        return $schema;
    }
    
    
    
    public function fetchByPrimary($id) {
        return $this->fetchFor($this->_model->getUnit($id));
    }
    
    public function fetchStoredUnitList() {
        return $this->_adapter->fetchStoredUnitList();
    }


    public function destroyStorage() {
        $this->_adapter->destroyStorage();
    }
    
    public function buildInitialSchema() {
        return new axis\schema\Base($this, $this->getUnitName());
    }

    public function updateUnitSchema(axis\schema\ISchema $schema) {
        return $schema;
    }
    
    public function validateUnitSchema(axis\schema\ISchema $schema) {
        return $schema;
    }
}
