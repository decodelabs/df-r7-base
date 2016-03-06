<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record;

use df;
use df\core;
use df\opal;
use df\user;
use df\mesh;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class ValuePreparationException extends RuntimeException {}


// Interfaces
interface IRecordAdapterProvider {
    public function getAdapter();
}

trait TRecordAdapterProvider {

    protected $_adapter;

    public function getAdapter() {
        return $this->_adapter;
    }
}


interface IPrimaryKeySetProvider extends IRecordAdapterProvider {
    public function getPrimaryKeySet();
    public function getOriginalPrimaryKeySet();
}

trait TPrimaryKeySetProvider {

    public function getPrimaryKeySet() {
        return $this->_getPrimaryKeySet(true);
    }

    public function getOriginalPrimaryKeySet() {
        return $this->_getPrimaryKeySet(false);
    }

    protected function _getPrimaryKeySet($includeChanges=true) {
        $fields = opal\schema\Introspector::getPrimaryFields($this->_adapter);

        if($fields === null) {
            if($this->_adapter) {
                throw new LogicException(
                    'Record type '.$this->_adapter->getQuerySourceId().' has no primary fields'
                );
            } else {
                throw new LogicException(
                    'Anonymous record has no primary fields'
                );
            }
        }

        return $this->_buildPrimaryKeySet($fields, $includeChanges);
    }

    abstract protected function _buildPrimaryKeySet(array $fields, $includeChanges=true);
}

trait TAccessLockProvider {

    public function getAccessLockDomain() {
        return $this->_adapter->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action=null) {
        return $this->_adapter->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null) {
        return $this->_adapter->getDefaultAccess($action);
    }

    public function getAccessLockId() {
        return $this->_adapter->getAccessLockId();
    }
}


interface IDataProvider extends core\collection\IMappedCollection, user\IAccessLock, mesh\entity\IEntity, IRecordAdapterProvider, IPrimaryKeySetProvider {
    public function getRaw($key);

    public function getValuesForStorage();

    public function populateWithPreparedData(array $row);
    public function populateWithRawData($row);
}

interface IRecord extends IDataProvider, core\IExporterValueMap {
    public function isNew();
    public function makeNew(array $newValues=null);
    public function spawnNew(array $newValues=null);

    public function hasChanged(...$fields);
    public function clearChanges();
    public function countChanges();
    public function getChanges();
    public function getChangedValues();
    public function getChangesForStorage();
    public function getUpdatedValues();
    public function getUpdatedValuesForStorage();
    public function getAddedValues();
    public function getAddedValuesForStorage();
    public function getRawId($key);
    public function getOriginal($key);
    public function getOriginalValues();
    public function getOriginalValuesForStorage();
    public function forceSet($key, $value);
    public function acceptChanges($insertId=null, array $insertData=null);
    public function markAsChanged($field);
    public function shouldBypassHooks($flag=null);

    public function save(opal\record\task\ITaskSet $taskSet=null);
    public function delete(opal\record\task\ITaskSet $taskSet=null);
    public function deploySaveTasks(opal\record\task\ITaskSet $taskSet);
    public function deployDeleteTasks(opal\record\task\ITaskSet $taskSet);
    public function triggerTaskEvent(opal\record\task\ITaskSet $taskSet, opal\record\task\IRecordTask $task, $when);
}

interface ILocationalRecord extends IRecord {
    public function getQueryLocation();
}



interface IPartial extends IDataProvider {
    public function setRecordAdapter(opal\query\IAdapter $adapter);
    public function isBridge($flag=null);
}



interface IValueContainer extends core\IUserValueContainer {
    public function getValueForStorage();
    public function duplicateForChangeList();
    public function eq($value);
    public function getDumpValue();
}

interface IPreparedValueContainer extends IValueContainer {
    public function isPrepared();
    public function prepareValue(opal\record\IRecord $record, $fieldName);
    public function prepareToSetValue(opal\record\IRecord $record, $fieldName);
}

interface IIdProviderValueContainer extends IValueContainer {
    public function getRawId();
}

interface ITaskAwareValueContainer extends IValueContainer {
    public function deploySaveTasks(opal\record\task\ITaskSet $taskSet, IRecord $record, $fieldName, opal\record\task\ITask $task=null);
    public function acceptSaveTaskChanges(opal\record\IRecord $record);
    public function deployDeleteTasks(opal\record\task\ITaskSet $taskSet, IRecord $record, $fieldName, opal\record\task\ITask $task=null);
    public function acceptDeleteTaskChanges(opal\record\IRecord $record);
}


interface IManyRelationValueContainer extends IValueContainer {
    public function add(...$records);
    public function addList(array $records);
    public function remove(...$records);
    public function removeList(array $records);
    public function removeAll();

    public function select(...$fields);
    public function fetch();

    //public function populateInverse(array $inverse);
}


interface IPrimaryKeySet extends \ArrayAccess, core\IArrayProvider {
    public function toArray();
    public function updateWith($record);
    public function countFields();
    public function getFieldNames();
    public function isNull();
    public function getCombinedId();
    public function getEntityId();
    public function getValue();
    public function getFirstKeyValue();
    public function duplicateWith($values);
    public function eq(IPrimaryKeySet $keySet);
}
