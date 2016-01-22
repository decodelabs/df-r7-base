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


interface IModel extends mesh\entity\IParentEntity, core\IRegistryObject {
    public function getModelName();
    public function getUnit($name);
    public static function getSchemaManager();
    public function unloadUnit(IUnit $unit);
    public static function purgeLiveCache();
}


interface IUnitOptions {
    const BACKUP_SUFFIX = '__bak_';
}



interface IUnit extends mesh\entity\IEntity, user\IAccessLock, \Serializable {

    const DEFAULT_ACCESS = axis\IAccess::GUEST;

    public function _setUnitName($name);
    public function getUnitName();
    public function getCanonicalUnitName();
    public function getUnitId();
    public function getUnitType();
    public function getModel();
    public function getUnitSettings();
    public function getStorageBackendName();

    public function prepareValidator(core\validate\IHandler $validator, opal\record\IRecord $record=null);
    public function beginProcedure($name, $values);

    public function getRoutine($name);
    public function executeRoutine($name);
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

    public function serialize() {
        return $this->getUnitId();
    }

    public function unserialize($data) {
        return axis\Model::loadUnitFromId($data);
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
        return $this->_model->getModelName().'/'.$this->getUnitName();
    }

    public function getUnitSettings() {
        if($this->_unitSettings === null) {
            $config = axis\Config::getInstance();
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

    public function getStorageBackendName() {
        return null;
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

    public function prepareValidator(core\validate\IHandler $validator, opal\record\IRecord $record=null) {
        return $validator;
    }



    public function beginProcedure($name, $values) {
        return axis\procedure\Base::factory($this, $name, $values);
    }

    public function getRoutine($name, core\io\IMultiplexer $multiplexer=null) {
        return axis\routine\Base::factory($this, $name, $multiplexer);
    }

    public function executeRoutine($name) {
        $args = array_slice(func_get_args(), 1);
        return call_user_func_array([$this->getRoutine($name), 'execute'], $args);
    }


// Mesh
    public function getEntityLocator() {
        return new mesh\entity\Locator(
            'axis://'.$this->_model->getModelName().'/'.ucfirst($this->getUnitName())
        );
    }


// Access
    public function getAccessLockDomain() {
        return 'model';
    }

    public function lookupAccessKey(array $keys, $action=null) {
        $id = $this->getUnitId();

        $parts = explode('/', $id);
        $test = $parts[0].'/';

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
    public function isVirtualUnitShared();
}

interface IAdapterBasedUnit {
    public function getUnitAdapter();
    public function getUnitAdapterName();
    public function getUnitAdapterConnectionName();
}

interface IStorageUnit extends IUnit {
    public function fetchByPrimary($id);
    public function destroyStorage();
    public function storageExists();
    public function getStorageGroupName();
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
        } else if($this->_adapter instanceof opal\query\IAdapter) {
            return $this->_adapter->getQuerySourceDisplayName();
        } else {
            core\stub($this->_adapter);
        }
    }

    protected function _loadAdapter() {
        $config = axis\Config::getInstance();
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
    public function getTransientUnitSchema($force=false);
    public function clearUnitSchemaCache();
    public function buildInitialSchema();
    public function updateUnitSchema(axis\schema\ISchema $schema);
    public function validateUnitSchema(axis\schema\ISchema $schema);

    public function ensureStorage();
    public function createStorageFromSchema(axis\schema\ISchema $schema);
    public function updateStorageFromSchema(axis\schema\ISchema $schema);
    public function customizeTranslatedSchema(opal\schema\ISchema $schema);

    public function getDefinedUnitSchemaVersion();

    public function getRecordNameField();
    public function getRecordKeyName();
}

trait TSchemaBasedStorageUnit {

    //const NAME_FIELD = null;
    //const KEY_NAME = null;

    private $_recordNameField = null;
    private $_recordKeyName = null;

    public function getRecordNameField() {
        if($this->_recordNameField === null) {
            if(static::NAME_FIELD) {
                $this->_recordNameField = static::NAME_FIELD;
            } else {
                $schema = $this->getUnitSchema();
                $try = ['name', 'title', 'id'];

                foreach($try as $field) {
                    if($field = $schema->getField($field)) {
                        $this->_recordNameField = $field->getName();
                        break;
                    }
                }

                if(!$this->_recordNameField) {
                    foreach($schema->getFields() as $field) {
                        if($field instanceof opal\schema\IMultiPrimitiveField
                        || $field instanceof opal\schema\INullPrimitiveField) {
                            continue;
                        }

                        $this->_recordNameField = $field->getName();
                        break;
                    }
                }

                if(!$this->_recordNameField) {
                    throw new RuntimeException(
                        'Unable to work out a suitable name field for '.$this->getUnitId()
                    );
                }
            }
        }

        return $this->_recordNameField;
    }

    public function getRecordKeyName() {
        if($this->_recordKeyName === null) {
            if(static::KEY_NAME) {
                $this->_recordKeyName = static::KEY_NAME;
            } else {
                $this->_recordKeyName = lcfirst($this->getUnitName());
            }
        }

        return $this->_recordKeyName;
    }

    public function customizeTranslatedSchema(opal\schema\ISchema $schema) {
        return $schema;
    }
}


interface IContext extends IUnit, core\IContext {}



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
    public function getStorageGroupName();
}

interface ISchemaProviderAdapter extends IAdapter {
    public function ensureStorage();
    public function createStorageFromSchema(axis\schema\ISchema $schema);
    public function updateStorageFromSchema(axis\schema\ISchema $schema);
    public function destroyStorage();
    public function storageExists();
}

interface INode {
    public function validate();
    public function isValid();
}
