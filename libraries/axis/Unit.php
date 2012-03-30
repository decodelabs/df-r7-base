<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;

abstract class Unit implements IUnit {
    
    const ID_SEPARATOR = '/';
    
    private $_unitName;
    protected $_model;
    
    public static function loadAdapter(axis\IAdapterBasedStorageUnit $unit) {
        $config = axis\ConnectionConfig::getInstance($unit->getModel()->getApplication());
        $adapterId = $config->getAdapterIdFor($unit);
        $unitType = $unit->getUnitType();
        
        if(empty($adapterId)) {
            throw new axis\RuntimeException(
                'No adapter has been configured for '.ucfirst($unit->getUnitType()).' unit type'
            );
        }
        
        $class = 'df\\axis\\unit\\'.lcfirst($unitType).'\\adapter\\'.$adapterId;
        
        if(!class_exists($class)) {
            throw new axis\RuntimeException(
                ucfirst($unit->getUnitType()).' unit adapter '.$adapterId.' could not be found'
            );
        }
        
        return new $class($unit);
    }
    
    
    public static function fromId($id, core\IApplication $application=null) {
        $parts = explode(self::ID_SEPARATOR, $id);
        $model = axis\Model::factory(array_shift($parts), $application);
        return $model->getUnit(array_shift($parts));
    }
    
    public static function factory(axis\IModel $model, $name) {
        $name = lcfirst($name);
        $class = 'df\\apex\\models\\'.$model->getModelName().'\\'.$name.'\\Unit';
        
        if(!class_exists($class)) {
            if(preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)\(([a-zA-Z0-9_., ]+)\)$/i', $name, $matches)) {
                $class = 'df\\axis\\unit\\'.$matches[1].'\\'.$matches[2];
                
                if(!class_exists($class)) {
                    throw new axis\RuntimeException(
                        'Virtual model unit type '.$model->getModelName().self::ID_SEPARATOR.$matches[1].'.'.$matches[2].' could not be found'
                    );
                }
                
                $ref = new \ReflectionClass($class);
                
                if(!$ref->implementsInterface('df\\axis\\IVirtualUnit')) {
                    throw new axis\RuntimeException(
                        'Unit type '.$model->getModelName().self::ID_SEPARATOR.$matches[1].'.'.$matches[2].' cannot load virtual units'
                    );
                }
                
                $args = core\string\Util::parseDelimited($matches[3]);
                $output = $class::loadVirtual($model, $args);
                $output->_unitName = $name;
                
                return $output;
            }
            
            
            throw new axis\RuntimeException(
                'Model unit '.$model->getModelName().self::ID_SEPARATOR.$name.' could not be found'
            );
        }
        
        return new $class($model);
    }
    
    public function __construct(axis\IModel $model) {
        $this->_model = $model;
    }
    
    public function getUnitName() {
        if(!$this->_unitName) {
            $parts = explode('\\', get_class($this));
            array_pop($parts);
            $this->_unitName = array_pop($parts);
        }
        
        return $this->_unitName;
    }
    
    public function getCanonicalUnitName() {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $this->getUnitName());
    }
    
    public function getUnitId() {
        return $this->_model->getModelName().self::ID_SEPARATOR.$this->getUnitName();
    }
    
    public function getModel() {
        return $this->_model;
    }
    
    public function getApplication() {
        return $this->_model->getApplication();
    }
}
