<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;
use df\flex;
use df\mesh;
use df\opal;

abstract class Model implements IModel, core\IDumpable {

    const REGISTRY_PREFIX = 'model://';

    private $_modelName;
    private $_units = [];

    public static function factory(string $name): IModel {
        $name = lcfirst($name);
        $key = self::REGISTRY_PREFIX.$name;

        if($model = df\Launchpad::$app->getRegistryObject($key)) {
            return $model;
        }

        $class = 'df\\apex\\models\\'.$name.'\\Model';

        if(!class_exists($class)) {
            throw new RuntimeException(
                'Model '.$name.' could not be found'
            );
        }

        $model = new $class();
        df\Launchpad::$app->setRegistryObject($model);

        return $model;
    }

    protected function __construct() {}

    public function getModelName() {
        if(!$this->_modelName) {
            $parts = explode('\\', get_class($this));
            array_pop($parts);
            $this->_modelName = array_pop($parts);
        }

        return $this->_modelName;
    }

    final public function getRegistryObjectKey(): string {
        return self::REGISTRY_PREFIX.$this->getModelName();
    }


// Units
    public function getUnit($name) {
        $name = lcfirst($name);

        if(isset($this->_units[$name])) {
            return $this->_units[$name];
        }

        if($name == 'context') {
            return $this->_units[$name] = new Context($this);
        }


        $class = 'df\\apex\\models\\'.$this->getModelName().'\\'.$name.'\\Unit';

        if(!class_exists($class)) {
            if(preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)\(([a-zA-Z0-9_.\, \/]*)\)$/i', $name, $matches)) {
                $class = 'df\\axis\\unit\\'.$matches[1].'\\'.$matches[2];

                if(!class_exists($class)) {
                    throw new axis\RuntimeException(
                        'Virtual model unit type '.$this->getModelName().'/'.$matches[1].'.'.$matches[2].' could not be found'
                    );
                }

                $ref = new \ReflectionClass($class);

                if(!$ref->implementsInterface('df\\axis\\IVirtualUnit')) {
                    throw new axis\RuntimeException(
                        'Unit type '.$this->getModelName().'/'.$matches[1].'.'.$matches[2].' cannot load virtual units'
                    );
                }

                $args = flex\Delimited::parse($matches[3]);
                $output = $class::loadVirtual($this, $args);
                $output->_setUnitName($name);

                return $output;
            }


            throw new axis\RuntimeException(
                'Model unit '.$this->getModelName().'/'.$name.' could not be found'
            );
        }

        $unit = new $class($this);
        $this->_units[$unit->getUnitName()] = $unit;

        return $unit;
    }

    public static function getSchemaManager() {
        return axis\schema\Manager::getInstance();
    }

    public function unloadUnit(IUnit $unit) {
        unset($this->_units[$unit->getUnitName()]);
        return $this;
    }

    public static function purgeLiveCache() {
        foreach(df\Launchpad::$app->findRegistryObjects(self::REGISTRY_PREFIX) as $key => $model) {
            $model->_purgeLiveCache();
        }
    }

    protected function _purgeLiveCache() {
        foreach($this->_units as $unit) {
            $this->unloadUnit($unit);
        }
    }

    public function __get($member) {
        return $this->getUnit($member);
    }

    public static function loadUnitFromId($id) {
        $parts = explode('/', $id, 2);

        return self::factory(array_shift($parts))
            ->getUnit(array_shift($parts));
    }

    public static function getUnitMetaData(array $unitIds) {
        $output = [];

        foreach($unitIds as $unitId) {
            if(isset($output[$unitId])) {
                continue;
            }

            @list($model, $name) = explode('/', $unitId, 2);

            $data = [
                'unitId' => $unitId,
                'model' => $model,
                'name' => $name,
                'canonicalName' => $name,
                'type' => null
            ];

            try {
                $unit = self::loadUnitFromId($unitId);
                $data['name'] = $unit->getUnitName();
                $data['canonicalName'] = $unit->getCanonicalUnitName();
                $data['type'] = $unit->getUnitType();
            } catch(axis\RuntimeException $e) {}

            $output[$unitId] = $data;
        }

        ksort($output);

        return $output;
    }


// Mesh
    public function getEntityLocator() {
        return new mesh\entity\Locator('axis://'.$this->getModelName());
    }

    public function fetchSubEntity(mesh\IManager $manager, array $node) {
        switch($node['type']) {
            case 'Unit':
                return $this->getUnit($node['id']);

            case 'Schema':
                $unit = $this->getUnit($node['id']);

                if(!$unit instanceof ISchemaBasedStorageUnit) {
                    throw new LogicException(
                        'Model unit '.$unit->getUnitName().' does not provide a schema'
                    );
                }

                return $unit->getUnitSchema();
        }
    }


// Dump
    public function getDumpProperties() {
        return [
            new core\debug\dumper\Property('name', $this->_modelName),
            new core\debug\dumper\Property('units', $this->_units, 'private')
        ];
    }
}
