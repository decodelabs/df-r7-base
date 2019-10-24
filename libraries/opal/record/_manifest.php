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

use DecodeLabs\Glitch;

// Exceptions
interface IException
{
}
class InvalidArgumentException extends \InvalidArgumentException implements IException
{
}
class RuntimeException extends \RuntimeException implements IException
{
}
class LogicException extends \LogicException implements IException
{
}


// Interfaces
interface IRecordAdapterProvider
{
    public function getAdapter();
}

trait TRecordAdapterProvider
{
    protected $_adapter;

    public function getAdapter()
    {
        return $this->_adapter;
    }
}


interface IPrimaryKeySetProvider extends IRecordAdapterProvider
{
    public function getPrimaryKeySet();
    public function getOriginalPrimaryKeySet();
}

trait TPrimaryKeySetProvider
{
    public function getPrimaryKeySet()
    {
        return $this->_getPrimaryKeySet(true);
    }

    public function getOriginalPrimaryKeySet()
    {
        return $this->_getPrimaryKeySet(false);
    }

    protected function _getPrimaryKeySet($includeChanges=true)
    {
        $fields = opal\schema\Introspector::getPrimaryFields($this->_adapter);

        if ($fields === null) {
            if ($this->_adapter) {
                throw Glitch::ELogic(
                    'Record type '.$this->_adapter->getQuerySourceId().' has no primary fields'
                );
            } else {
                throw Glitch::ELogic(
                    'Anonymous record has no primary fields'
                );
            }
        }

        return $this->_buildPrimaryKeySet($fields, $includeChanges);
    }

    abstract protected function _buildPrimaryKeySet(array $fields, $includeChanges=true);
}

trait TAccessLockProvider
{
    public function getAccessLockDomain()
    {
        return $this->_adapter->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action=null)
    {
        return $this->_adapter->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null)
    {
        return $this->_adapter->getDefaultAccess($action);
    }

    public function getAccessSignifiers(): array
    {
        return $this->_adapter->getAccessSignifiers();
    }

    public function getAccessLockId()
    {
        return $this->_adapter->getAccessLockId();
    }
}


interface IDataProvider extends
    core\collection\IMappedCollection,
    user\IAccessLock,
    mesh\entity\IEntity,
    IRecordAdapterProvider,
    IPrimaryKeySetProvider
{
    public function getRaw($key);

    public function getValuesForStorage();

    public function populateWithPreparedData(array $row);
    public function populateWithRawData($row);
}

interface IRecord extends IDataProvider, mesh\job\IJobProvider, core\IExporterValueMap
{
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
    public function shouldBypassHooks(bool $flag=null);

    public function save(mesh\job\IQueue $queue=null);
    public function delete(mesh\job\IQueue $queue=null);
    public function deploySaveJobs(mesh\job\IQueue $queue);
    public function deployDeleteJobs(mesh\job\IQueue $queue);
    public function triggerJobEvent(mesh\job\IQueue $queue, opal\record\IJob $job, $when);
}

interface ILocationalRecord extends IRecord
{
    public function getQueryLocation();
}



interface IPartial extends IDataProvider
{
    public function setRecordAdapter(opal\query\IAdapter $adapter);
    public function isBridge(bool $flag=null);
}



interface IValueContainer extends core\IUserValueContainer
{
    public function getValueForStorage();
    public function duplicateForChangeList();
    public function eq($value);
    public function getDumpValue();
}

interface IPreparedValueContainer extends IValueContainer
{
    public function isPrepared();
    public function prepareValue(opal\record\IRecord $record, $fieldName);
    public function prepareToSetValue(opal\record\IRecord $record, $fieldName);
}

interface IIdProviderValueContainer extends IValueContainer
{
    public function getRawId();
}

interface IJobAwareValueContainer extends IValueContainer
{
    public function deploySaveJobs(mesh\job\IQueue $queue, IRecord $record, $fieldName, mesh\job\IJob $job=null);
    public function acceptSaveJobChanges(opal\record\IRecord $record);
    public function deployDeleteJobs(mesh\job\IQueue $queue, IRecord $record, $fieldName, mesh\job\IJob $job=null);
    public function acceptDeleteJobChanges(opal\record\IRecord $record);
}


interface IManyRelationValueContainer extends IValueContainer
{
    public function add(...$records);
    public function addList(array $records);
    public function remove(...$records);
    public function removeList(array $records);
    public function removeAll();

    public function select(...$fields);
    public function fetch();

    //public function populateInverse(array $inverse);
}


interface IPrimaryKeySet extends \ArrayAccess, core\IArrayProvider
{
    public function getKeys(): array;
    public function getKeyMap($fieldName): array;
    public function getIntrinsicFieldMap($fieldName=null): array;
    public function updateWith($record);
    public function countFields(): int;
    public function getFieldNames(): array;
    public function isNull(): bool;
    public function getCombinedId(): string;
    public function getEntityId(): string;
    public function getValue();
    public function getFirstKeyValue();
    public function getRawValue();
    public function duplicateWith($values);
    public function eq(IPrimaryKeySet $keySet);
}



###############
## Jobs
interface IJob extends mesh\job\IJob, mesh\job\IEventBroadcastingJob
{
    const EVENT_PRE = 'pre';
    const EVENT_EXECUTE = 'execute';
    const EVENT_POST = 'post';

    public function getRecord();
    public function getRecordJobName();
}



trait TJob
{
    protected $_record;
    protected $_reportEvents = true;

    public function getObjectId(): string
    {
        return mesh\job\Queue::getObjectId($this->_record);
    }

    public function getRecord()
    {
        return $this->_record;
    }

    public function getAdapter(): ?mesh\job\ITransactionAdapter
    {
        return $this->_record->getAdapter();
    }

    public function shouldReportEvents(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_reportEvents = $flag;
            return $this;
        }

        return $this->_reportEvents;
    }

    public function reportPreEvent(mesh\job\IQueue $queue)
    {
        if ($this->_reportEvents) {
            $this->_record->triggerJobEvent($queue, $this, opal\record\IJob::EVENT_PRE);
        }

        return $this;
    }

    public function reportExecuteEvent(mesh\job\IQueue $queue)
    {
        if ($this->_reportEvents) {
            $this->_record->triggerJobEvent($queue, $this, opal\record\IJob::EVENT_EXECUTE);
        }

        return $this;
    }

    public function reportPostEvent(mesh\job\IQueue $queue)
    {
        if ($this->_reportEvents) {
            $this->_record->triggerJobEvent($queue, $this, opal\record\IJob::EVENT_POST);
        }

        return $this;
    }
}
