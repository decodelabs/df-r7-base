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
use df\opal;

abstract class Model implements IModel, core\IDumpable {
    
    use core\TApplicationAware;
    
    const REGISTRY_PREFIX = 'model://';
    
    private $_modelName;
    private $_clusterId = null;
    private $_units = [];
    
    public static function factory($name, $clusterId=null, core\IApplication $application=null) {
        if($name instanceof IModel) {
            return $name;
        }

        if($clusterId instanceof core\IApplication) {
            $application = $clusterId;
            $clusterId = null;
        }
        
        if(!$application) {
            $application = df\Launchpad::getActiveApplication();
        }

        $parts = explode(':', $name, 2);
        $name = lcfirst(array_pop($parts));

        if(!empty($parts)) {
            $clusterId = array_shift($parts);
        }

        $key = self::REGISTRY_PREFIX;

        if($clusterId) {
            $key .= $clusterId.'/';
        }

        $key .= $name;
        
        if($model = $application->getRegistryObject($key)) {
            return $model;
        }
        
        $class = 'df\\apex\\models\\'.$name.'\\Model';
        
        if(!class_exists($class)) {
            throw new RuntimeException(
                'Model '.$name.' could not be found'
            );
        }
        
        $model = new $class($application, $clusterId);
        $application->setRegistryObject($model);
        
        return $model;
    }
    
    public function __construct(core\IApplication $application, $clusterId=null) {
        $this->_application = $application;
        $this->_clusterId = $clusterId;
    }
    
    public function getModelName() {
        if(!$this->_modelName) {
            $parts = explode('\\', get_class($this));
            array_pop($parts);
            $this->_modelName = array_pop($parts);
        }
        
        return $this->_modelName;
    }

    public function getModelId() {
        $output = $this->getModelName();

        if($this->_clusterId) {
            $output = $this->_clusterId.':'.$output;
        }

        return $output;
    }

    public function getClusterId() {
        return $this->_clusterId;
    }
    
    final public function getRegistryObjectKey() {
        $key = self::REGISTRY_PREFIX;

        if($this->_clusterId) {
            $key .= $this->_clusterId.'/';
        }

        $key .= $this->getModelName();
        return $key;
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
        if($this->_clusterId) {
            $model = $this->factory($this->getModelName(), null, $this->_application);
        } else {
            $model = $this;
        }

        return $model->getUnit('schemaDefinition.Virtual()');
    }
    
    public function unloadUnit(IUnit $unit) {
        unset($this->_units[$unit->getUnitName()]);
        return $this;
    }
    
    public function __get($member) {
        return $this->getUnit($member);
    }

    public static function loadUnitFromId($id, $clusterId=null, core\IApplication $application=null) {
        $parts = explode(IUnit::ID_SEPARATOR, $id, 2);
        $nameParts = explode(':', array_shift($parts), 2);
        $name = array_pop($nameParts);

        if($clusterId === null && !empty($nameParts)) {
            $clusterId = array_shift($nameParts);
        }

        return self::factory($name, $clusterId, $application)
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


// Clusters
    public static function loadClusterUnit(core\IApplication $application=null) {
        $config = axis\ConnectionConfig::getInstance($application);
        $unitId = $config->getClusterUnitId();

        if(!$unitId) {
            throw new RuntimeException(
                'No cluster unit has been defined in config'
            );
        }

        return self::loadUnitFromId($unitId, null, $application);
    }

    public static function createCluster($clusterId, core\IApplication $application=null) {
        $config = axis\ConnectionConfig::getInstance($application);

        foreach($config->getConnectionsOfType('Rdbms') as $set) {
            try {
                $dsn = opal\rdbms\Dsn::factory(@$set['dsn']);
                $dsn->setDatabaseSuffix('_'.$clusterId);
                $connection = opal\rdbms\adapter\Base::factory($dsn, true);
                $connection->getDatabase()->truncate();
            } catch(\Exception $e) {
                continue;
            }
        }
    }

    public static function renameCluster($oldId, $newId, core\IApplication $application=null) {
        $config = axis\ConnectionConfig::getInstance($application);

        foreach($config->getConnectionsOfType('Rdbms') as $set) {
            try {
                $dsn = opal\rdbms\Dsn::factory(@$set['dsn']);
                $dsn->setDatabaseSuffix('_'.$oldId);
                $connection = opal\rdbms\adapter\Base::factory($dsn, true);
                $dsn = clone $dsn;
                $dsn->setDatabaseSuffix('_'.$newId);
                $connection->getDatabase()->rename($dsn->getDatabase());
            } catch(\Exception $e) {
                continue;
            }
        }
    }

    public static function dropCluster($clusterId, core\IApplication $application=null) {
        $config = axis\ConnectionConfig::getInstance($application);

        foreach($config->getConnectionsOfType('Rdbms') as $set) {
            try {
                $dsn = opal\rdbms\Dsn::factory(@$set['dsn']);
                $dsn->setDatabaseSuffix('_'.$clusterId);
                $connection = opal\rdbms\adapter\Base::factory($dsn, false);
                $connection->getDatabase()->drop();
            } catch(\Exception $e) {
                continue;
            }
        }
    }

// Mesh
    public function getEntityLocator() {
        $output = 'axis://';

        if($this->_clusterId) {
            $output .= $this->_clusterId.'/';
        }

        $output .= $this->getModelName();
        return new mesh\entity\Locator($output);
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
            new core\debug\dumper\Property('cluster', $this->_clusterId),
            new core\debug\dumper\Property('units', $this->_units, 'private')
        ];
    }
}
