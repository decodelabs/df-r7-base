<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;

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
    
    public function getUnit($name) {
        $name = lcfirst($name);
        
        if(isset($this->_units[$name])) {
            return $this->_units[$name];
        }
        
        $unit = axis\Unit::factory($this, $name);
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
    
    
// Policy
    public function getEntityLocator() {
        return new core\policy\EntityLocator('axis://Model:'.$this->getModelName());
    }

    public function fetchSubEntity(core\policy\IManager $manager, core\policy\IEntityLocatorNode $node) {
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
