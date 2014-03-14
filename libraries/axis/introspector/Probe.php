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
    
    use core\TApplicationAware;

    public function __construct(core\IApplication $application) {
        $this->_application = $application;
    }

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
        $config = axis\ConnectionConfig::getInstance($this->_application);

        $unitList = array_merge(
            $this->getDefinedUnitList(),
            $config->getDefinedUnits()
        );

        $output = [];
        $adapters = [];

        foreach($unitList as $unitId) {
            $unit = axis\Model::loadUnitFromId($unitId);
            $output[$unitId] = $inspector = new UnitInspector($unit);

            if($adapter = $inspector->getAdapter()) {
                $adapters[$adapter->getQuerySourceAdapterHash()] = $adapter;
            }
        }

        foreach($adapters as $adapter) {
            if(!$adapter instanceof axis\ISchemaProviderAdapter) {
                continue;
            }

            $schemaDefinition = new axis\unit\schemaDefinition\Virtual($adapter->getUnit()->getModel());

            foreach($schemaDefinition->fetchStoredUnitList() as $unitId) {
                if(isset($output[$unitId])) {
                    continue;
                }

                $unit = axis\Model::loadUnitFromId($unitId);
                $output[$unitId] = new UnitInspector($unit);
            }
        }

        ksort($output);
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