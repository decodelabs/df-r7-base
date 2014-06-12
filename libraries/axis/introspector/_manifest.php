<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\introspector;

use df;
use df\core;
use df\axis;
use df\opal;

// Exceptions
interface IException {}


// Interfaces
interface IProbe extends core\IApplicationAware {
    public function getModelList();
    public function getDefinedUnitList();
    public function getDefinedUnitListForModel($modelName);
    public function probeUnits();
    public function inspectUnit($id);
}

interface IUnitInspector {
    public function getUnit();
    public function getModel();
    public function getId();
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
    public function getTransientSchema();

    public function getBackups();
    public function describeStorage($name);
    public function storageExists();
}

interface IStorageDescriber {
    public function getName();
    public function getType();
    public function getItemCount();
    public function getSize();
    public function getIndexSize();
    public function getCreationDate();
}