<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\R7\Legacy;

use df\axis;
use df\flex;
use df\mesh;

abstract class Model implements IModel, Dumpable
{
    public const REGISTRY_PREFIX = 'model://';

    private $_modelName;
    private $_units = [];

    public static function factory(string $name)
    {
        $name = lcfirst($name);
        $key = self::REGISTRY_PREFIX . $name;

        if ($model = Legacy::getRegistryObject($key)) {
            return $model;
        }

        $class = 'df\\apex\\models\\' . $name . '\\Model';

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Model ' . $name . ' could not be found'
            );
        }

        $model = new $class();
        Legacy::setRegistryObject($model);

        return $model;
    }

    protected function __construct()
    {
    }

    public function getModelName()
    {
        if (!$this->_modelName) {
            $parts = explode('\\', get_class($this));
            array_pop($parts);
            $this->_modelName = array_pop($parts);
        }

        return $this->_modelName;
    }

    final public function getRegistryObjectKey(): string
    {
        return self::REGISTRY_PREFIX . $this->getModelName();
    }


    // Units
    public function getUnit($name)
    {
        $lookupName = lcfirst((string)$name);

        if (isset($this->_units[$lookupName])) {
            return $this->_units[$lookupName];
        }

        if ($lookupName == 'context') {
            return $this->_units[$lookupName] = new Context($this);
        }


        $class = 'df\\apex\\models\\' . $this->getModelName() . '\\' . $lookupName . '\\Unit';

        if (!class_exists($class)) {
            if (preg_match('/^([a-z0-9_.]+)\(([a-zA-Z0-9_.\, \/]*)\)$/i', (string)$name, $matches)) {
                $className = $matches[1];

                // Fix legacy
                if ($className === 'table.Bridge') {
                    $className = 'BridgeTable';
                } else {
                    $className = ucfirst($className);
                }

                $class = 'df\\axis\\unit\\' . $className;

                if (!class_exists($class)) {
                    throw Exceptional::NotFound(
                        'Virtual model unit type ' . $this->getModelName() . '/' . $className . ' could not be found'
                    );
                }

                $ref = new \ReflectionClass($class);

                if (!$ref->implementsInterface('df\\axis\\IVirtualUnit')) {
                    throw Exceptional::Runtime(
                        'Unit type ' . $this->getModelName() . '/' . $className . ' cannot load virtual units'
                    );
                }

                $args = flex\Delimited::parse($matches[2]);
                $output = $class::loadVirtual($this, $args);
                $output->_setUnitName($name);

                return $output;
            }


            throw Exceptional::NotFound(
                'Model unit ' . $this->getModelName() . '/' . $name . ' could not be found'
            );
        }

        $unit = new $class($this);
        $this->_units[$unit->getUnitName()] = $unit;

        return $unit;
    }

    public static function getSchemaManager()
    {
        return axis\schema\Manager::getInstance();
    }

    public function unloadUnit(IUnit $unit)
    {
        unset($this->_units[$unit->getUnitName()]);
        return $this;
    }

    public static function purgeLiveCache()
    {
        foreach (Legacy::findRegistryObjects(self::REGISTRY_PREFIX) as $key => $model) {
            /** @var Model $model */
            $model->_purgeLiveCache();
        }
    }

    protected function _purgeLiveCache()
    {
        foreach ($this->_units as $unit) {
            $this->unloadUnit($unit);
        }
    }

    public function __get($member)
    {
        return $this->getUnit($member);
    }

    public static function loadUnitFromId($id)
    {
        $parts = explode('/', $id, 2);

        return self::factory((string)array_shift($parts))
            ->getUnit((string)array_shift($parts));
    }

    public static function getUnitMetaData(array $unitIds)
    {
        $output = [];

        foreach ($unitIds as $unitId) {
            if (isset($output[$unitId])) {
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
            } catch (axis\Exception $e) {
            }

            $output[$unitId] = $data;
        }

        ksort($output);

        return $output;
    }


    // Mesh
    public function getEntityLocator()
    {
        return new mesh\entity\Locator('axis://' . $this->getModelName());
    }

    public function fetchSubEntity(mesh\IManager $manager, array $node)
    {
        switch ($node['type']) {
            case 'Unit':
                return $this->getUnit($node['id']);

            case 'Schema':
                $unit = $this->getUnit($node['id']);

                if (!$unit instanceof ISchemaBasedStorageUnit) {
                    throw Exceptional::Logic(
                        'Model unit ' . $unit->getUnitName() . ' does not provide a schema'
                    );
                }

                return $unit->getUnitSchema();
        }
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'definition' => $this->_modelName;
        yield 'values' => $this->_units;
    }
}
