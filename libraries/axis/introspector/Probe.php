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

class Probe implements IProbe {
    
    public function getModelList() {
        $output = [];

        foreach(df\Launchpad::$loader->lookupFolderList('apex/models') as $name => $dir) {
            if(class_exists('df\\apex\\models\\'.$name.'\\Model')) {
                $output[] = $name;
            }
        }

        return $output;
    }

    public function getDefinedUnitList() {
        $output = [];

        foreach($this->getModelList() as $modelName) {
            foreach($this->getDefinedUnitListForModel($modelName) as $unitId) {
                $output[] = $modelName.'/'.$unitId;
            }
        }

        return $output;
    }

    public function getDefinedUnitListForModel($modelName) {
        $output = [];

        foreach(df\Launchpad::$loader->lookupFolderList('apex/models/'.$modelName) as $name => $dir) {
            $output[] = $name;
        }

        return $output;
    }

    public function probeUnits() {
        $config = axis\Config::getInstance();

        $unitList = array_merge(
            $this->getDefinedUnitList(),
            $config->getDefinedUnits()
        );

        $output = [];
        $adapters = [];

        foreach($unitList as $unitId) {
            $unit = axis\Model::loadUnitFromId($unitId);
            $inspector = new UnitInspector($unit);

            if($inspector->isSharedVirtual()) {
                continue;
            }

            $output[$unitId] = $inspector;

            if($adapter = $inspector->getQueryAdapter()) {
                $adapters[$adapter->getQuerySourceAdapterHash()] = $adapter;
            } else if($adapter = $inspector->getAdapter()) {
                $adapters[$inspector->getAdapterName()] = $adapter;
            }
        }

        $schemaManager = axis\schema\Manager::getInstance();

        foreach($schemaManager->fetchStoredUnitList() as $unitId) {
            if(isset($output[$unitId])) {
                continue;
            }

            try {
                $unit = axis\Model::loadUnitFromId($unitId);
            } catch(axis\RuntimeException $e) {
                continue;
            }
            
            $output[$unitId] = new UnitInspector($unit);
        }

        ksort($output);
        return $output;
    }

    public function probeStorageUnits() {
        $output = $this->probeUnits();

        foreach($output as $key => $unit) {
            if(!$unit->isStorageUnit()) {
                unset($output[$key]);
            }
        }
        
        return $output;
    }

    public function inspectUnit($id) {
        if($id instanceof axis\IUnit) {
            $unit = $id;
        } else {
            $unit = axis\Model::loadUnitFromId($id);
        }

        return new UnitInspector($unit);
    }
}