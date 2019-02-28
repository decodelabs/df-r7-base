<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema;

use df;
use df\core;
use df\axis;

class Manager implements IManager
{
    use core\TManager;

    const REGISTRY_PREFIX = 'manager://axis/schema';

    protected $_transient = [];
    protected $_storeSchema;

    public function fetchFor(axis\ISchemaBasedStorageUnit $unit, $transient=false)
    {
        $schema = null;
        $cache = Cache::getInstance();
        $globalUnitId = $unit->getUnitId();
        $isStoreUnit = $globalUnitId == 'axis/schema';

        if ($isStoreUnit && $this->_storeSchema) {
            return $this->_storeSchema;
        }

        if (!($transient && isset($this->_transient[$unit->getUnitId()]))) {
            $schema = $cache->get($globalUnitId);

            if ($schema !== null && !$schema instanceof ISchema) {
                $schema = null;
                $cache->clear();
            }

            $setCache = false;
            $schemaJson = null;

            if (!$schema && !$isStoreUnit) {
                $schemaJson = $this->getSchemaUnit()->select('schema')
                    ->where('unitId', '=', $globalUnitId)
                    ->toValue('schema');
            }

            if (!$schema && $schemaJson) {
                $schema = Base::fromJson($unit, $schemaJson);
                $setCache = true;
            }
        }


        if (!$schema) {
            $schema = $unit->buildInitialSchema();
            $unit->updateUnitSchema($schema);
            $setCache = false;

            if (!$transient) {
                $unit->validateUnitSchema($schema);

                if (!$unit->storageExists()) {
                    $unit->createStorageFromSchema($schema);
                }

                $schema->acceptChanges();

                if ($isStoreUnit) {
                    $this->_storeSchema = $schema;
                }

                $this->store($unit, $schema);

                if ($isStoreUnit) {
                    $this->_storeSchema = null;
                }

                $setCache = true;
            }
        }

        if ($setCache) {
            $cache->set($globalUnitId, $schema);
        }

        return $schema;
    }

    public function store(axis\ISchemaBasedStorageUnit $unit, ISchema $schema)
    {
        $currentTimestamp = $this->getTimestampFor($unit);
        $schema->acceptChanges();
        $jsonData = $schema->toJson();

        if ($currentTimestamp === null) {
            $this->insert($unit, $jsonData, $schema->getVersion());
        } else {
            $this->update($unit, $jsonData, $schema->getVersion());
        }

        $this->clearCache($unit);
        return $this;
    }

    public function getTimestampFor(axis\ISchemaBasedStorageUnit $unit)
    {
        return $this->getSchemaUnit()->select('timestamp')
            ->where('unitId', '=', $unit->getUnitId())
            ->toValue('timestamp');
    }

    public function insert(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version)
    {
        $this->getSchemaUnit()->replace([
                'unitId' => $unit->getUnitId(),
                'storeName' => $unit->getStorageBackendName(),
                'version' => $version,
                'schema' => $jsonData
            ])
            ->execute();

        return $this;
    }

    public function update(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version)
    {
        $this->getSchemaUnit()->update([
                'schema' => $jsonData,
                'version' => $version,
                'timestamp' => 'now'
            ])
            ->where('unitId', '=', $unit->getUnitId())
            ->execute();

        return $this;
    }


    public function remove(axis\ISchemaBasedStorageUnit $unit)
    {
        return $this->removeId($unit->getUnitId());
    }

    public function removeId($unitId)
    {
        $this->getSchemaUnit()->delete()
            ->where('unitId', '=', $unitId)
            ->execute();

        $cache = Cache::getInstance();
        $cache->remove($unitId);

        return $this;
    }

    public function clearCache(axis\ISchemaBasedStorageUnit $unit=null)
    {
        $cache = Cache::getInstance();

        if ($unit) {
            $cache->remove($unit->getUnitId());
        } else {
            $cache->clear();
        }

        return $this;
    }

    public function fetchStoredUnitList()
    {
        $unit = $this->getSchemaUnit();

        if (!$unit->storageExists()) {
            return [];
        }

        return $unit->select('unitId')
            ->orderBy('unitId ASC')
            ->toList('unitId');
    }

    public function markTransient(axis\ISchemaBasedStorageUnit $unit)
    {
        $this->_transient[$unit->getUnitId()] = true;
        return $this;
    }

    public function unmarkTransient(axis\ISchemaBasedStorageUnit $unit)
    {
        unset($this->_transient[$unit->getUnitId()]);
        return $this;
    }

    public function getSchemaUnit()
    {
        return axis\Model::loadUnitFromId('axis/schema');
    }
}
