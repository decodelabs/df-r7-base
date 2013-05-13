<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;
use df\user;

abstract class Unit implements IUnit {
    
    use user\TAccessLock;
    
    const ID_SEPARATOR = '/';

    const DEFAULT_ACCESS = axis\IAccess::GUEST;
    public static $actionAccess = [];
    
    private $_unitName;
    private $_unitSettings;

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

        if($name == 'context') {
            return new Context($model);
        }

        $class = 'df\\apex\\models\\'.$model->getModelName().'\\'.$name.'\\Unit';
        
        if(!class_exists($class)) {
            if(preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)\(([a-zA-Z0-9_., ]*)\)$/i', $name, $matches)) {
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
                $unit = self::fromId($unitId);
                $data['name'] = $unit->getUnitName();
                $data['canonicalName'] = $unit->getCanonicalUnitName();
                $data['type'] = $unit->getUnitType();
            } catch(axis\RuntimeException $e) {}

            $output[$unitId] = $data;
        }

        ksort($output);

        return $output;
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

    public function getUnitSettings() {
        if($this->_unitSettings === null) {
            $config = axis\ConnectionConfig::getInstance($this->_model->getApplication());
            $this->_unitSettings = $config->getSettingsFor($this);
        }

        return $this->_unitSettings;
    }

    protected function _shouldPrefixNames() {
        $settings = $this->getUnitSettings();
        return (bool)$settings['prefixNames'];
    }
    
    public function getModel() {
        return $this->_model;
    }
    
    public function getApplication() {
        return $this->_model->getApplication();
    }


// Access
    public function getAccessLockDomain() {
        return 'model';
    }

    public function lookupAccessKey(array $keys, $action=null) {
        $id = $this->getUnitId();

        $parts = explode(self::ID_SEPARATOR, $id);
        $test = $parts[0].self::ID_SEPARATOR;

        if($action !== null) {
            if(isset($keys[$id.'#'.$action])) {
                return $keys[$id.'#'.$action];
            }

            if(isset($keys[$test.'*#'.$action])) {
                return $keys[$test.'*#'.$action];
            }

            if(isset($keys[$test.'%#'.$action])) {
                return $keys[$test.'%#'.$action];
            }

            if(isset($keys['*#'.$action])) {
                return $keys['*#'.$action];
            }
        }


        if(isset($keys[$id])) {
            return $keys[$id];
        }

        if(isset($keys[$test.'*'])) {
            return $keys[$test.'*'];
        }

        if(isset($keys[$test.'%'])) {
            return $keys[$test.'%'];
        }

        return null;
    }

    public function getDefaultAccess($action=null) {
        if($action === null) {
            return static::DEFAULT_ACCESS;
        }

        if(isset(static::$actionAccess[$action])) {
            return static::$actionAccess[$action];
        }

        return static::DEFAULT_ACCESS;
    }

    public function getAccessLockId() {
        return $this->getUnitId();
    }


// Policy
    public function getEntityLocator() {
        return new core\policy\EntityLocator('axis://Unit:"'.$this->getUnitId().'"');
    }
}
