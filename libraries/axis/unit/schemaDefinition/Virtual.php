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

    public function getUnitName() {
        return 'schemaDefinition.Virtual()';
    }

    public function getStorageGroupName() {
        return $this->_adapter->getStorageGroupName();
    }
    
    public function getStorageBackendName() {
        $output = 'axis_schemas';

        if($this->_shouldPrefixNames()) {
            $output = df\Launchpad::$application->getUniquePrefix().'_'.$output;
        }

        return $output;
    }

    public function isVirtualUnitShared() {
        return false;
    }
    
    public function fetchFor(axis\ISchemaBasedStorageUnit $unit, $transient=false) {
        $cache = axis\schema\Cache::getInstance();
        $schema = $cache->get($unit->getGlobalUnitId());
        
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
            $setCache = false;
            
            if(!$transient) {
                $unit->validateUnitSchema($schema);
                $unit->updateStorageFromSchema($schema);
                
                $schema->acceptChanges();
                $this->store($unit, $schema);
                
                $setCache = true;
            }
        }

        if($setCache) {    
            $cache->set($unit->getGlobalUnitId(), $schema);
        }
        
        return $schema;
    }

    
    public function store(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $currentTimestamp = $this->_adapter->getTimestampFor($unit);
        $schema->acceptChanges();
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

    public function update(axis\ISchemaBasedStorageUnit $unit) {
        if($unit->getClusterId()) {
            $unit = axis\Model::loadUnitFromId($unit->getGlobalUnitId());
        }

        $schema = $unit->getUnitSchema();
        $unit->updateUnitSchema($schema);
        $unitId = $unit->getGlobalUnitId();
        $store = [];

        if($schema->hasPrimaryIndexChanged()) {
            foreach($this->fetchStoredUnitList() as $relationUnitId) {
                $relationUnit = axis\Model::loadUnitFromId($relationUnitId);
                $relationSchema = $relationUnit->getUnitSchema();
                $update = false;

                foreach($relationSchema->getFields() as $relationField) {
                    if(!$relationField instanceof axis\schema\IRelationField
                    || $relationField instanceof opal\schema\INullPrimitiveField
                    || $relationField->getTargetUnitId() != $unitId) {
                        continue;
                    }

                    if($relationField instanceof opal\schema\IOneRelationField) {
                        $relationField->markAsChanged();
                        $relationSchema->replacePreparedField($relationField);
                        $update = true;
                    } else {
                        core\stub($relationField, $relationUnit);
                    }
                }

                if($update) {
                    $relationSchema->sanitize($relationUnit);

                    if($relationUnit->storageExists()) {
                        $relationUnit->updateStorageFromSchema($relationSchema);
                    }

                    $store[$relationUnit->getUnitId()] = [
                        'unit' => $relationUnit,
                        'schema' => $relationSchema
                    ];
                }
            }
        }

        if($unit->storageExists()) {
            $unit->updateStorageFromSchema($schema);
        }

        $store[$unit->getUnitId()] = [
            'unit' => $unit,
            'schema' => $schema
        ];


        try {
            $clusterUnit = axis\Model::loadClusterUnit();
        } catch(axis\RuntimeException $e) {
            $clusterUnit = null;
        }

        if($clusterUnit) {
            foreach($clusterUnit->select('@primary as primary') as $row) {
                $clusterId = $row['primary'];

                foreach($store as $unitId => $set) {
                    $clusterUnit = axis\Model::loadUnitFromId($unitId, $clusterId);

                    if($clusterUnit->storageExists()) {
                        $clusterUnit->updateStorageFromSchema($set['schema']);
                    }
                }
            }
        }


        foreach($store as $unitId => $set) {
            $this->store($set['unit'], $set['schema']);
        }

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

    public function removeId($unitId) {
        $parts = explode('/', $unitId, 2);
        $modelParts = explode(':', array_shift($parts));
        $unitId = array_pop($modelParts).'/'.array_shift($parts);

        $this->_adapter->removeId($unitId);
        return $this;
    }
    
    public function clearUnitSchemaCache() {
        return $this->clearCache($this);
    }
    
    public function clearCache(axis\ISchemaBasedStorageUnit $unit=null) {
        $cache = axis\schema\Cache::getInstance();
        
        if($unit) {
            $cache->remove($unit->getGlobalUnitId());
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
        $schema->iterateVersion();
        
        return $schema;
    }
    
    
    
    public function fetchByPrimary($id) {
        return $this->fetchFor($this->_model->getUnit($id));
    }
    
    public function fetchStoredUnitList() {
        try {
            return $this->_adapter->fetchStoredUnitList();
        } catch(\Exception $e) {
            $this->_adapter->ensureStorage();
            return $this->_adapter->fetchStoredUnitList();
        }
    }


    public function destroyStorage() {
        $this->_adapter->destroyStorage();
        return $this;
    }

    public function storageExists() {
        return $this->_adapter->storageExists();
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

    public function getDefinedUnitSchemaVersion() {
        return 1;
    }
}
