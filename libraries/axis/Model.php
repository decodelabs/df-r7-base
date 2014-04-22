<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;
use df\mesh;

abstract class Model implements IModel, core\IDumpable {
    
    use core\TApplicationAware;
    
    const REGISTRY_PREFIX = 'model://';
    
    private $_modelName;
    private $_units = array();
    
    public static function factory($name, core\IApplication $application=null) {
        if($name instanceof IModel) {
            return $name;
        }
        
        if(!$application) {
            $application = df\Launchpad::getActiveApplication();
        }
        
        $name = lcfirst($name);
        
        if($model = $application->getRegistryObject(self::REGISTRY_PREFIX.$name)) {
            return $model;
        }
        
        $class = 'df\\apex\\models\\'.$name.'\\Model';
        
        if(!class_exists($class)) {
            throw new RuntimeException(
                'Model '.$name.' could not be found'
            );
        }
        
        $model = new $class($application);
        $application->setRegistryObject($model);
        
        return $model;
    }
    
    public function __construct(core\IApplication $application) {
        $this->_application = $application;
    }
    
    public function getModelName() {
        if(!$this->_modelName) {
            $parts = explode('\\', get_class($this));
            array_pop($parts);
            $this->_modelName = array_pop($parts);
        }
        
        return $this->_modelName;
    }
    
    final public function getRegistryObjectKey() {
        return self::REGISTRY_PREFIX.$this->getModelName();
    }

    public function onApplicationShutdown() {}
    

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
                        'Virtual model unit type '.$this->getModelName().IUnit::ID_SEPARATOR.$matches[1].'.'.$matches[2].' could not be found'
                    );
                }
                
                $ref = new \ReflectionClass($class);
                
                if(!$ref->implementsInterface('df\\axis\\IVirtualUnit')) {
                    throw new axis\RuntimeException(
                        'Unit type '.$this->getModelName().IUnit::ID_SEPARATOR.$matches[1].'.'.$matches[2].' cannot load virtual units'
                    );
                }
                
                $args = core\string\Util::parseDelimited($matches[3]);
                $output = $class::loadVirtual($this, $args);
                $output->_setUnitName($name);
                
                return $output;
            }
            
            
            throw new axis\RuntimeException(
                'Model unit '.$this->getModelName().IUnit::ID_SEPARATOR.$name.' could not be found'
            );
        }
        
        $unit = new $class($this);
        $this->_units[$unit->getUnitName()] = $unit;
        
        return $unit;
    }


    public function getSchemaDefinitionUnit() {
        return $this->getUnit('schemaDefinition.Virtual()');
    }
    
    public function unloadUnit(IUnit $unit) {
        unset($this->_units[$unit->getUnitName()]);
        return $this;
    }
    
    public function __get($member) {
        return $this->getUnit($member);
    }

    public static function loadUnitFromId($id, core\IApplication $application=null) {
        $parts = explode(IUnit::ID_SEPARATOR, $id, 2);

        return self::factory(array_shift($parts), $application)
            ->getUnit(array_shift($parts));
    }

    public static function getUnitMetaData(array $unitIds) {
        $output = array();

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
        return new mesh\entity\Locator('axis://Model:'.$this->getModelName());
    }

    public function fetchSubEntity(mesh\IManager $manager, mesh\entity\ILocatorNode $node) {
        $id = $node->getId();
        
        switch($node->getType()) {
            case 'Unit':
                return $this->getUnit($id);
                
            case 'Schema':
                $unit = $this->getUnit($id);
                
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
