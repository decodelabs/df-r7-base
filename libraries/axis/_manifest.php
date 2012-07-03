<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;
use df\opal;


// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}

// Interfaces
interface IModel extends core\IApplicationAware, core\policy\IParentEntity, core\IRegistryObject {
    public function getModelName();
    
    public function getUnit($name);
    public function unloadUnit(IUnit $unit);
}





interface IUnit extends core\IApplicationAware {
    public function getUnitName();
    public function getCanonicalUnitName();
    public function getUnitId();
    public function getModel();
    public function getUnitSettings();
}

interface IVirtualUnit extends IUnit {
    public static function loadVirtual(IModel $model, array $args);
}

interface IStorageUnit extends IUnit {
    public function fetchByPrimary($id);
    public function destroyStorage();
    public function getStorageBackendName();
}

interface IAdapterBasedStorageUnit extends IStorageUnit {
    public function getUnitAdapter();
    public function getUnitType();
}

interface ISchemaBasedStorageUnit extends IAdapterBasedStorageUnit, opal\schema\ISchemaContext {
    public function getUnitSchema();
    public function getTransientUnitSchema();
    public function clearUnitSchemaCache();
    public function buildInitialSchema();
    public function updateUnitSchema(axis\schema\ISchema $schema);
    public function validateUnitSchema(axis\schema\ISchema $schema);
}

interface ISchemaDefinitionStorageUnit extends IStorageUnit {
    public function fetchFor(ISchemaBasedStorageUnit $unit, $transient=false);
    public function store(ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema);
    public function remove(ISchemaBasedStorageUnit $unit);
    public function clearCache(ISchemaBasedStorageUnit $unit=null);
}




interface IAdapter {}

interface ISchemaProviderAdapter extends IAdapter {
    public function createStorageFromSchema(axis\schema\ISchema $schema);
    public function destroyStorage();
}


interface ISchemaDefinitionStorageAdapter extends ISchemaProviderAdapter {
    public function fetchFor(ISchemaBasedStorageUnit $unit);
    public function getTimestampFor(ISchemaBasedStorageUnit $unit);
    
    public function insert(ISchemaBasedStorageUnit $unit, $jsonData, $version);
    public function update(ISchemaBasedStorageUnit $unit, $jsonData, $version);
    public function remove(ISchemaBasedStorageUnit $unit);
    
    public function ensureStorage();
}
