<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\introspector;

// Interfaces
interface IProbe
{
    public function getModelList();
    public function getDefinedUnitList();
    public function getDefinedUnitListForModel($modelName);
    public function probeUnits();
    public function probeStorageUnits();
    public function inspectUnit($id);
}

interface IUnitInspector
{
    public function getUnit();
    public function getModel();
    public function getId(): string;
    public function getCanonicalId();
    public function getType();
    public function isVirtual();

    public function hasAdapter();
    public function hasQueryAdapter();
    public function getAdapter();
    public function getQueryAdapter();
    public function getAdapterName();
    public function getAdapterConnectionName();

    public function getSchema();
    public function getTransientSchema($force = false);
    public function getSchemaVersion();
    public function getDefinedSchemaVersion();
    public function canUpdateSchema();

    public function isStorageUnit();
    public function isSchemaBasedStorageUnit();
    public function getBackups();
    public function describeStorage($name);
    public function storageExists();
}

interface IStorageDescriber
{
    public function getName(): string;
    public function getType();
    public function getItemCount();
    public function getSize();
    public function getIndexSize();
    public function getCreationDate();
}
