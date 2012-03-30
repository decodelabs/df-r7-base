<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;


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
}

interface IVirtualUnit extends IUnit {
    public static function loadVirtual(IModel $model, array $args);
}

interface IStorageUnit extends IUnit {
    public function fetchByPrimary($id);
    public function destroyStorage();
}

interface IAdapterBasedStorageUnit extends IStorageUnit {
    public function getUnitAdapter();
    public function getUnitType();
}

interface ISchemaBasedStorageUnit extends IAdapterBasedStorageUnit {
    public function getUnitSchema();
    public function getTransientUnitSchema();
    public function clearUnitSchemaCache();
    public function updateUnitSchema(axis\schema\ISchema $schema);
    public function validateUnitSchema(axis\schema\ISchema $schema);
}






interface IAdapter {}

interface ISchemaProviderAdapter extends IAdapter {
    public function fetchSchema();
    public function storeSchema(axis\schema\ISchema $schema);
    public function unstoreSchema();
    
    public function createStorageFromSchema(axis\schema\ISchema $schema);
    public function destroyStorage();
}