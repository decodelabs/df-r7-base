<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis;

use DecodeLabs\Exceptional;
use DecodeLabs\Fluidity\Cast;
use DecodeLabs\Fluidity\CastTrait;
use DecodeLabs\Glitch;
use DecodeLabs\R7\Config\DataConnections as AxisConfig;
use df\axis;
use df\core;
use df\mesh;
use df\opal;
use df\user;

interface IAccess extends user\IState
{
}


interface IModel extends mesh\entity\IParentEntity, core\IRegistryObject
{
    public function getModelName();
    public function getUnit($name);
    public static function getSchemaManager();
    public function unloadUnit(IUnit $unit);
    public static function purgeLiveCache();
}


interface IUnitOptions
{
    public const BACKUP_SUFFIX = '__bak_';
}



interface IUnit extends
    mesh\entity\IEntity,
    user\IAccessLock,
    Cast
{
    public const DEFAULT_ACCESS = axis\IAccess::GUEST;

    public function _setUnitName($name);
    public function getUnitName();
    public function getCanonicalUnitName();
    public function getUnitId();
    public function getUnitType();
    public function getModel();
    public function getUnitSettings();
    public function getStorageBackendName();

    public function prepareValidator(core\validate\IHandler $validator, opal\record\IRecord $record = null);
    public function beginProcedure($name, $values);
}

trait TUnit
{
    use CastTrait;
    use user\TAccessLock;

    private $_unitName;
    private $_unitSettings;

    protected $_model;

    public function __construct(axis\IModel $model)
    {
        $this->_model = $model;
    }


    public function _setUnitName($name)
    {
        $this->_unitName = $name;
        return $this;
    }

    public function getUnitName()
    {
        if (!$this->_unitName) {
            $parts = explode('\\', get_class($this));
            array_pop($parts);
            $this->_unitName = array_pop($parts);
        }

        return $this->_unitName;
    }

    public function getCanonicalUnitName()
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $this->getUnitName());
    }

    public function getUnitId()
    {
        return $this->_model->getModelName() . '/' . $this->getUnitName();
    }

    public function getUnitSettings()
    {
        if ($this->_unitSettings === null) {
            $config = AxisConfig::load();
            $this->_unitSettings = $config->getSettingsFor($this);
        }

        return $this->_unitSettings;
    }

    protected function _shouldPrefixNames()
    {
        $settings = $this->getUnitSettings();
        return (bool)$settings['prefixNames'];
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function getStorageBackendName()
    {
        return null;
    }

    public function getContext()
    {
        return $this->_model->getUnit('context');
    }

    public function __get($member)
    {
        switch ($member) {
            case 'model':
                return $this->_model;

            case 'settings':
                return $this->getUnitSettings();

            case 'context':
                return $this->_model->getUnit('context');
        }
    }

    public function prepareValidator(core\validate\IHandler $validator, opal\record\IRecord $record = null)
    {
        return $validator;
    }



    public function beginProcedure($name, $values)
    {
        return axis\procedure\Base::factory($this, $name, $values);
    }


    // Mesh
    public function getEntityLocator()
    {
        return new mesh\entity\Locator(
            'axis://' . $this->_model->getModelName() . '/' . ucfirst($this->getUnitName())
        );
    }


    // Access
    public function getAccessLockDomain()
    {
        return 'model';
    }

    public function lookupAccessKey(array $keys, $action = null)
    {
        $id = $this->getUnitId();

        $parts = explode('/', $id);
        $test = $parts[0] . '/';

        if ($action !== null) {
            if (isset($keys[$id . '#' . $action])) {
                return $keys[$id . '#' . $action];
            }

            if (isset($keys[$test . '*#' . $action])) {
                return $keys[$test . '*#' . $action];
            }

            if (isset($keys[$test . '%#' . $action])) {
                return $keys[$test . '%#' . $action];
            }

            if (isset($keys['*#' . $action])) {
                return $keys['*#' . $action];
            }
        }


        if (isset($keys[$id])) {
            return $keys[$id];
        }

        if (isset($keys[$test . '*'])) {
            return $keys[$test . '*'];
        }

        if (isset($keys[$test . '%'])) {
            return $keys[$test . '%'];
        }

        return null;
    }

    public function getDefaultAccess($action = null)
    {
        return static::DEFAULT_ACCESS;
    }

    public function getAccessLockId()
    {
        return $this->getUnitId();
    }
}



interface IVirtualUnit extends IUnit
{
    public static function loadVirtual(IModel $model, array $args);
    public function isVirtualUnitShared();
}

interface IAdapterBasedUnit
{
    public function getUnitAdapter();
    public function getUnitAdapterName();
    public function getUnitAdapterConnectionName();
}

interface IStorageUnit extends IUnit
{
    public function fetchByPrimary($id);
    public function destroyStorage();
    public function storageExists();
    public function getStorageGroupName();
}

interface IAdapterBasedStorageUnit extends IStorageUnit, IAdapterBasedUnit
{
}

trait TAdapterBasedStorageUnit
{
    protected $_adapter;

    public function getUnitAdapter()
    {
        return $this->_adapter;
    }

    public function getUnitAdapterName()
    {
        return $this->_adapter->getDisplayName();
    }

    public function getUnitAdapterConnectionName()
    {
        if ($this->_adapter instanceof axis\IConnectionProxyAdapter) {
            return $this->_adapter->getConnectionDisplayName();
        } elseif ($this->_adapter instanceof opal\query\IAdapter) {
            return $this->_adapter->getQuerySourceDisplayName();
        } else {
            Glitch::incomplete($this->_adapter);
        }
    }

    protected function _loadAdapter()
    {
        $config = AxisConfig::load();
        $adapterId = $config->getAdapterIdFor($this);
        $unitType = $this->getUnitType();

        if (empty($adapterId)) {
            throw Exceptional::Runtime(
                'No adapter has been configured for ' . ucfirst($this->getUnitType()) . ' unit type'
            );
        }

        $class = 'df\\axis\\unit\\' . lcfirst($unitType) . '\\adapter\\' . $adapterId;

        if (!class_exists($class)) {
            throw Exceptional::Runtime(
                ucfirst($this->getUnitType()) . ' unit adapter ' . $adapterId . ' could not be found'
            );
        }

        $this->_adapter = new $class($this);
    }
}

interface ISchemaBasedStorageUnit extends IAdapterBasedStorageUnit, opal\schema\ISchemaContext
{
    public function getUnitSchema();
    public function getTransientUnitSchema($force = false);
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
    public function getRecordPriorityFields(): array;
    public function getRecordKeyName();
}

trait TSchemaBasedStorageUnit
{
    //const NAME_FIELD = null;
    //const KEY_NAME = null;
    //const PRIORITY_FIELDS = [];

    private $_recordNameField = null;
    private $_recordKeyName = null;

    public function getRecordNameField()
    {
        if ($this->_recordNameField === null) {
            if (static::NAME_FIELD) {
                $this->_recordNameField = static::NAME_FIELD;
            } else {
                $schema = $this->getUnitSchema();
                $try = ['name', 'title', 'id'];

                foreach ($try as $field) {
                    if ($field = $schema->getField($field)) {
                        $this->_recordNameField = $field->getName();
                        break;
                    }
                }

                if (!$this->_recordNameField) {
                    foreach ($schema->getFields() as $field) {
                        if ($field instanceof opal\schema\IMultiPrimitiveField
                        || $field instanceof opal\schema\INullPrimitiveField) {
                            continue;
                        }

                        $this->_recordNameField = $field->getName();
                        break;
                    }
                }

                if (!$this->_recordNameField) {
                    throw Exceptional::Runtime(
                        'Unable to work out a suitable name field for ' . $this->getUnitId()
                    );
                }
            }
        }

        return $this->_recordNameField;
    }

    public function getRecordKeyName()
    {
        if ($this->_recordKeyName === null) {
            if (static::KEY_NAME) {
                $this->_recordKeyName = static::KEY_NAME;
            } else {
                $this->_recordKeyName = lcfirst($this->getUnitName());
            }
        }

        return $this->_recordKeyName;
    }

    public function getRecordPriorityFields(): array
    {
        if (is_array(static::PRIORITY_FIELDS ?? null)) {
            $output = static::PRIORITY_FIELDS;
        } else {
            $output = [];
        }

        $nameField = $this->getRecordNameField();

        if (!in_array($nameField, $output)) {
            $output[] = $nameField;
        }

        return $output;
    }

    public function customizeTranslatedSchema(opal\schema\ISchema $schema)
    {
        return $schema;
    }
}


interface IContext extends IUnit, core\IContext
{
}



interface IAdapter
{
    public function getDisplayName(): string;
    public function getUnit();
}

interface IConnectionProxyAdapter extends IAdapter
{
    public function getConnection();
    public function getConnectionDisplayName();
}

interface IIntrospectableAdapter extends IAdapter
{
    public function getStorageList();
    public function describeStorage($name = null);
    public function destroyDescribedStorage($name);
    public function getStorageGroupName();
}

interface ISchemaProviderAdapter extends IAdapter
{
    public function ensureStorage();
    public function createStorageFromSchema(axis\schema\ISchema $schema);
    public function updateStorageFromSchema(axis\schema\ISchema $schema);
    public function destroyStorage();
    public function storageExists();
}

interface INode
{
    public function validate();
    public function isValid(): bool;
}
