<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;
use df\opal;
use df\user;
use df\mesh;


// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}

// Interfaces
interface IAccess extends user\IState {}


interface IModel extends core\IApplicationAware, mesh\entity\IParentEntity, core\IRegistryObject {
    public function getModelName();
    public function getModelId();
    public function getClusterId();
    
    public function getUnit($name);
    public function getSchemaDefinitionUnit();
    public function unloadUnit(IUnit $unit);
}





interface IUnit extends core\IApplicationAware, mesh\entity\IEntity, user\IAccessLock {

    const ID_SEPARATOR = '/';
    const DEFAULT_ACCESS = axis\IAccess::GUEST;
    const BACKUP_SUFFIX = '__bak_';

    public function _setUnitName($name);
    public function getUnitName();
    public function getCanonicalUnitName();
    public function getUnitId();
    public function getGlobalUnitId();
    public function getUnitType();
    public function getModel();
    public function getClusterId();
    public function getUnitSettings();
}

trait TUnit {

    use user\TAccessLock;

    public static $actionAccess = [];
    
    private $_unitName;
    private $_unitSettings;

    protected $_model;

    public function __construct(axis\IModel $model) {
        $this->_model = $model;
    }

    public function _setUnitName($name) {
        $this->_unitName = $name;
        return $this;
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
        return $this->_model->getModelId().IUnit::ID_SEPARATOR.$this->getUnitName();
    }

    public function getGlobalUnitId() {
        return $this->_model->getModelName().IUnit::ID_SEPARATOR.$this->getUnitName();
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

    public function getClusterId() {
        return $this->_model->getClusterId();
    }
    
    public function getApplication() {
        return $this->_model->getApplication();
    }

    public function getContext() {
        return $this->_model->getUnit('context');
    }

    public function __get($member) {
        switch($member) {
            case 'model':
                return $this->_model;

            case 'settings':
                return $this->getUnitSettings();

            case 'context':
                return $this->_model->getUnit('context');
        }
    }

// Mesh
    public function getEntityLocator() {
        $output = 'axis://';

        if($clusterId = $this->getClusterId()) {
            $output .= $clusterId.'/';
        }

        $output .= $this->_model->getModelName().'/'.ucfirst($this->getUnitName());
        return new mesh\entity\Locator($output);
    }


// Access
    public function getAccessLockDomain() {
        return 'model';
    }

    public function lookupAccessKey(array $keys, $action=null) {
        $id = $this->getUnitId();

        $parts = explode(IUnit::ID_SEPARATOR, $id);
        $test = $parts[0].IUnit::ID_SEPARATOR;

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
}



interface IVirtualUnit extends IUnit {
    public static function loadVirtual(IModel $model, array $args);
}

interface IAdapterBasedUnit {
    public function getUnitAdapter();
    public function getUnitAdapterName();
    public function getUnitAdapterConnectionName();
}

interface IStorageUnit extends IUnit {
    public function fetchByPrimary($id);
    public function destroyStorage();
    public function getStorageBackendName();
}

interface IAdapterBasedStorageUnit extends IStorageUnit, IAdapterBasedUnit {}

trait TAdapterBasedStorageUnit {

    protected $_adapter;

    public function getUnitAdapter() {
        return $this->_adapter;
    }

    public function getUnitAdapterName() {
        return $this->_adapter->getDisplayName();
    }

    public function getUnitAdapterConnectionName() {
        if($this->_adapter instanceof axis\IConnectionProxyAdapter) {
            return $this->_adapter->getConnectionDisplayName();
        } else {
            // This needs to be something better!
            return $this->_adapter->getQuerySourceDisplayName();
        }
    }

    protected function _loadAdapter() {
        $config = axis\ConnectionConfig::getInstance($this->getModel()->getApplication());
        $adapterId = $config->getAdapterIdFor($this);
        $unitType = $this->getUnitType();
        
        if(empty($adapterId)) {
            throw new axis\RuntimeException(
                'No adapter has been configured for '.ucfirst($this->getUnitType()).' unit type'
            );
        }
        
        $class = 'df\\axis\\unit\\'.lcfirst($unitType).'\\adapter\\'.$adapterId;
        
        if(!class_exists($class)) {
            throw new axis\RuntimeException(
                ucfirst($this->getUnitType()).' unit adapter '.$adapterId.' could not be found'
            );
        }
        
        $this->_adapter = new $class($this);
    }
}

interface ISchemaBasedStorageUnit extends IAdapterBasedStorageUnit, opal\schema\ISchemaContext {
    public function getUnitSchema();
    public function getTransientUnitSchema();
    public function clearUnitSchemaCache();
    public function buildInitialSchema();
    public function updateUnitSchema(axis\schema\ISchema $schema);
    public function validateUnitSchema(axis\schema\ISchema $schema);
}

interface ISchemaDefinitionStorageUnit extends IStorageUnit {
    public function fetchFor(ISchemaBasedStorageUnit $unit, $transient=false);
    public function store(ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema);
    public function remove(ISchemaBasedStorageUnit $unit);
    public function removeId($unitId);
    public function clearCache(ISchemaBasedStorageUnit $unit=null);
    public function fetchStoredUnitList();
}


interface IContext extends IUnit, core\IContext, core\i18n\translate\ITranslationProxy {

}




interface IAdapter {
    public function getDisplayName();
    public function getUnit();
}

interface IConnectionProxyAdapter extends IAdapter {
    public function getConnection();
    public function getConnectionDisplayName();
}

interface IIntrospectableAdapter extends IAdapter {
    public function getStorageList();
    public function describeStorage($name=null);
    public function destroyDescribedStorage($name);
}

interface ISchemaProviderAdapter extends IAdapter {
    public function createStorageFromSchema(axis\schema\ISchema $schema);
    public function destroyStorage();
}


interface ISchemaDefinitionStorageAdapter extends ISchemaProviderAdapter {
    public function fetchFor(ISchemaBasedStorageUnit $unit);
    public function getTimestampFor(ISchemaBasedStorageUnit $unit);
    
    public function insert(ISchemaBasedStorageUnit $unit, $jsonData, $version);
    public function update(ISchemaBasedStorageUnit $unit, $jsonData, $version);
    public function remove(ISchemaBasedStorageUnit $unit);
    public function removeId($unitId);
    
    public function ensureStorage();
}
