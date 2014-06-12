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

class UnitInspector implements IUnitInspector, core\IDumpable {
        
    protected $_unit;

    public function __construct(axis\IUnit $unit) {
        $this->_unit = $unit;
    }

    public function getUnit() {
        return $this->_unit;
    }

    public function getModel() {
        return $this->_unit->getModel();
    }

    public function getId() {
        return $this->_unit->getUnitId();
    }

    public function getCanonicalId() {
        if($this->_unit instanceof axis\IStorageUnit) {
            return $this->_unit->getStorageBackendName();
        } else {
            return $this->_unit->getModel()->getModelName().'_'.$this->_unit->getCanonicalUnitName();
        }
    }

    public function getType() {
        return $this->_unit->getUnitType();
    }

    public function isVirtual() {
        return $this->_unit instanceof axis\IVirtualUnit;
    }

// Adapter
    public function hasAdapter() {
        return $this->_unit instanceof axis\IAdapterBasedUnit;
    }

    public function hasQueryAdapter() {
        return $this->_unit instanceof axis\IAdapterBasedStorageUnit;
    }

    public function getAdapter() {
        if($this->hasAdapter()) {
            return $this->_unit->getUnitAdapter();
        } 
    }

    public function getQueryAdapter() {
        if($this->hasQueryAdapter()) {
            return $this->_unit->getUnitAdapter();
        }
    }

    public function getAdapterName() {
        if($this->hasAdapter()) {
            return $this->_unit->getUnitAdapterName();
        }
    }

    public function getAdapterConnectionName() {
        if($this->hasAdapter()) {
            return $this->_unit->getUnitAdapterConnectionName();
        }
    }


// Schema
    public function getSchema() {
        if(!$this->_unit instanceof axis\ISchemaBasedStorageUnit) {
            return null;
        }

        return $this->_unit->getUnitSchema();
    }

    public function getTransientSchema() {
        if(!$this->_unit instanceof axis\ISchemaBasedStorageUnit) {
            return null;
        }

        return $this->_unit->getTransientUnitSchema();
    }


// Storage
    public function describeStorage($name) {
        $adapter = $this->getAdapter();
        $output = [];

        if(!$adapter instanceof axis\IIntrospectableAdapter) {
            return $output;
        }

        return $adapter->describeStorage($name);
    }

    public function storageExists() {
        if(!$this->_unit instanceof axis\ISchemaBasedStorageUnit) {
            return false;
        }

        return $this->_unit->storageExists();
    }

    public function getBackups() {
        $adapter = $this->getAdapter();
        $output = [];

        if(!$adapter instanceof axis\IIntrospectableAdapter) {
            return $output;
        }

        $storageList = $adapter->getStorageList();
        $check = $this->_unit->getStorageBackendName().axis\IUnit::BACKUP_SUFFIX;
        $length = strlen($check);

        foreach($storageList as $name) {
            if(substr($name, 0, $length) != $check) {
                continue;
            }

            $output[] = $adapter->describeStorage($name);
        }

        return $output;
    }


// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->getId(),
            'canonicalId' => $this->getCanonicalId(),
            'type' => $this->getType()
        ];
    }
}