<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\record;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;
use df\core;

use df\mesh;
use df\opal;
use df\user;

class Base implements IRecord, \Serializable, Dumpable
{
    use TRecordAdapterProvider;
    use TPrimaryKeySetProvider;
    use core\TValueMap;
    use core\collection\TExtractList;
    use core\collection\TExtricable;

    use TAccessLockProvider, user\TAccessLock {
        TAccessLockProvider::getAccessSignifiers insteadof user\TAccessLock;
    }
    public const BROADCAST_HOOK_EVENTS = null;

    protected $_values = [];
    protected $_changes = [];
    protected $_isPopulated = false;
    protected $_bypassHooks = false;

    public static function extractRecordId($record)
    {
        $keySet = null;
        $isRecord = false;

        if ($record instanceof IPrimaryKeySetProvider) {
            $isRecord = true;

            try {
                $keySet = $record->getPrimaryKeySet();
            } catch (\Throwable $e) {
                $keySet = null;
            }
        } elseif ($record instanceof IPrimaryKeySet) {
            $keySet = $record;
        }

        if ($keySet && !$keySet->isNull()) {
            return $keySet->getCombinedId();
        }

        if ($isRecord) {
            return '(#' . spl_object_hash($record) . ')';
        }

        if (is_array($record)) {
            return '{' . implode(PrimaryKeySet::COMBINE_SEPARATOR, $record) . '}';
        }

        return (string)$record;
    }

    public function __construct(opal\query\IAdapter $adapter, $row = null, array $fields = null)
    {
        $this->_adapter = $adapter;
        $fields = opal\schema\Introspector::getRecordFields($adapter, $fields);

        if (!empty($fields)) {
            $this->_values = array_fill_keys($fields, null);
        }


        // Prepare value slots
        foreach (opal\schema\Introspector::getFieldProcessors($adapter) as $name => $field) {
            $this->_values[$name] = $field->inflateValueFromRow($name, [], $this);
        }

        // Import initial values
        if ($row !== null) {
            $this->import($row);
        }
    }

    public function serialize()
    {
        return serialize($this->__serialize());
    }

    public function __serialize(): array
    {
        return [
            'adapter' => $this->_adapter->getQuerySourceId(),
            'values' => $this->getValuesForStorage()
        ];
    }

    public function unserialize(string $data): void
    {
        $data = unserialize($data);
        $this->__unserialize($data);
    }

    public function __unserialize(array $data): void
    {
        $adapter = mesh\Manager::getInstance()->fetchEntity($data['adapter']);

        $this->__construct($adapter);
        $this->populateWithRawData($data['values']);
    }

    public function isNew()
    {
        return !$this->_isPopulated;
    }

    public function makeNew(array $newValues = null)
    {
        $this->_isPopulated = false;

        // Clear primary values
        if (null !== ($fields = opal\schema\Introspector::getPrimaryFields($this->_adapter))) {
            foreach ($fields as $field) {
                unset($this->_changes[$field]);

                if ($this->_values[$field] instanceof IValueContainer) {
                    $this->_values[$field]->setValue(null);
                } else {
                    $this->_values[$field] = null;
                }
            }
        }

        if ($newValues !== null) {
            $this->import($newValues);
        }

        return $this;
    }

    public function spawnNew(array $newValues = null)
    {
        $output = clone $this;
        return $output->makeNew($newValues);
    }


    protected function _buildPrimaryKeySet(array $fields, $includeChanges = true)
    {
        $values = [];

        foreach ($fields as $field) {
            if ($includeChanges && array_key_exists($field, $this->_changes)) {
                $values[$field] = $this->_changes[$field];
            } elseif (isset($this->_values[$field])) {
                $values[$field] = $this->_values[$field];
            } else {
                $values[$field] = null;
            }

            if ($values[$field] instanceof IValueContainer) {
                $values[$field] = $values[$field]->getValueForStorage();
            }
        }

        return new PrimaryKeySet($fields, $values);
    }



    // Job
    public function prepareJob(string $name, ...$args): mesh\job\IJob
    {
        switch ($name) {
            case 'save':
                return $this->isNew() ?
                    new opal\record\job\Insert($this) :
                    new opal\record\job\Update($this);

            case 'insert':
                return new opal\record\job\Insert($this);

            case 'replace':
                return new opal\record\job\Replace($this);

            case 'update':
                return new opal\record\job\Update($this);

            case 'delete':
                return new opal\record\job\Delete($this);
        }

        throw Exceptional::Runtime(
            'Records don\'t directly support "' . $name . '" jobs'
        );
    }



    // Changes
    public function hasChanged(...$fields)
    {
        if (empty($fields)) {
            return !empty($this->_changes);
        }

        foreach (core\collection\Util::leaves($fields) as $field) {
            if (empty($field)) {
                continue;
            }

            if (array_key_exists($field, $this->_changes)) {
                return true;
            }
        }

        return false;
    }

    public function clearChanges()
    {
        $this->_changes = [];
        return $this;
    }

    public function countChanges()
    {
        return count($this->_changes);
    }

    public function getChanges()
    {
        return $this->_changes;
    }

    public function getChangedValues()
    {
        $output = [];

        foreach ($this->_changes as $key => $value) {
            if ($value instanceof IPreparedValueContainer && !$value->isPrepared()) {
                $value->prepareValue($this, $key);
            }

            if ($value instanceof IValueContainer) {
                $value = $value->getValue();
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function getChangesForStorage()
    {
        $output = [];

        foreach ($this->_changes as $key => $value) {
            if ($value instanceof IValueContainer) {
                $value = $value->getValueForStorage();
            } elseif ($value instanceof IPrimaryKeySetProvider) {
                $value = $value->getPrimaryKeySet();
            }

            $output[$key] = $this->_deflateValue($key, $value);
        }

        return $output;
    }

    public function getValuesForStorage()
    {
        $output = array_merge($this->_values, $this->_changes);

        foreach ($output as $key => $value) {
            if ($value instanceof IValueContainer) {
                $value = $value->getValueForStorage();
            } elseif ($value instanceof IPrimaryKeySetProvider) {
                $value = $value->getPrimaryKeySet();
            }

            $output[$key] = $this->_deflateValue($key, $value);
        }

        return $output;
    }

    public function getUpdatedValues()
    {
        $output = [];

        foreach ($this->_changes as $key => $value) {
            if (array_key_exists($key, $this->_values) && $value !== null) {
                if ($value instanceof IPreparedValueContainer && !$value->isPrepared()) {
                    $value->prepareValue($this, $key);
                }

                if ($value instanceof IValueContainer) {
                    $value = $value->getValue();
                }

                $output[$key] = $value;
            }
        }

        return $output;
    }

    public function getUpdatedValuesForStorage()
    {
        $output = [];

        foreach ($this->_changes as $key => $value) {
            if (array_key_exists($key, $this->_values) && $value !== null) {
                if ($value instanceof IValueContainer) {
                    $value = $value->getValueForStorage();
                } elseif ($value instanceof IPrimaryKeySetProvider) {
                    $value = $value->getPrimaryKeySet();
                }

                $output[$key] = $this->_deflateValue($key, $value);
            }
        }

        return $output;
    }

    public function getAddedValues()
    {
        $output = [];

        foreach ($this->_changes as $key => $value) {
            if (!array_key_exists($key, $this->_values) && $this->_changes[$key] !== null) {
                if ($value instanceof IPreparedValueContainer && !$value->isPrepared()) {
                    $value->prepareValue($this, $key);
                }

                if ($value instanceof IValueContainer) {
                    $value = $value->getValue();
                }

                $output[$key] = $value;
            }
        }

        return $output;
    }

    public function getAddedValuesForStorage()
    {
        $output = [];

        foreach ($this->_changes as $key => $value) {
            if (!array_key_exists($key, $this->_values) && $this->_changes[$key] !== null) {
                if ($value instanceof IValueContainer) {
                    $value = $value->getValueForStorage();
                } elseif ($value instanceof IPrimaryKeySetProvider) {
                    $value = $value->getPrimaryKeySet();
                }

                $output[$key] = $this->_deflateValue($key, $value);
            }
        }

        return $output;
    }

    public function getOriginalValues()
    {
        $output = [];

        foreach ($this->_values as $key => $value) {
            if ($value instanceof IPreparedValueContainer && !$value->isPrepared()) {
                $value->prepareValue($this, $key);
            }

            if ($value instanceof IValueContainer) {
                $value = $value->getValue();
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function getOriginalValuesForStorage()
    {
        $output = [];

        foreach ($this->_values as $key => $value) {
            if ($value instanceof IValueContainer) {
                $value = $value->getValueForStorage();
            } elseif ($value instanceof IPrimaryKeySetProvider) {
                $value = $value->getPrimaryKeySet();
            }

            $output[$key] = $this->_deflateValue($key, $value);
        }

        return $output;
    }

    public function acceptChanges($insertId = null, array $insertData = null)
    {
        $oldValues = $this->_values;
        $this->_values = array_merge($this->_values, $this->_changes);
        $this->clearChanges();

        // Normalize values
        foreach (opal\schema\Introspector::getFieldProcessors($this->_adapter) as $name => $field) {
            $this->_values[$name] = $field->normalizeSavedValue(
                $this->_values[$name],
                $this
            );
        }

        $this->_isPopulated = false;

        // Import primary key
        if ($insertId !== null && (null !== ($primaryFields = opal\schema\Introspector::getPrimaryFields($this->_adapter)))) {
            if (!$insertId instanceof IPrimaryKeySet) {
                if (count($primaryFields) > 1) {
                    // Cant do anything here??
                } else {
                    $insertId = new PrimaryKeySet($primaryFields, [$primaryFields[0] => $insertId]);
                }
            }

            foreach ($insertId->getKeys() as $field => $value) {
                $value = $this->_inflateValue($field, $value);

                if ($this->_values[$field] instanceof IValueContainer) {
                    $this->_values[$field]->setValue($value);
                } else {
                    $this->_values[$field] = $value;
                }
            }
        }


        // Merge insert data
        foreach ($this->_values as $key => $value) {
            if ($value === null && isset($insertData[$key])) {
                $this->_values[$key] = $value = $insertData[$key];
            }

            if ($value instanceof IJobAwareValueContainer) {
                $value->acceptSaveJobChanges($this);
            }
        }

        $this->_isPopulated = true;

        return $this;
    }


    public function markAsChanged(string $field)
    {
        if (!array_key_exists($field, $this->_changes)) {
            $oldVal = null;

            if (isset($this->_values[$field])) {
                $oldVal = $this->_values[$field];

                if ($oldVal instanceof IValueContainer) {
                    $oldVal = $oldVal->duplicateForChangeList();
                }
            }

            $this->_changes[$field] = $oldVal;
            //$this->onValueChange($field, $oldVal, $oldVal);
        }

        return $this;
    }

    public function markAsUnchanged(string $field)
    {
        unset($this->_changes[$field]);
        return $this;
    }


    public function populateWithPreparedData(array $row)
    {
        /*
        if($this->_isPopulated) {
            throw Exceptional::Runtime(
                'Record has already been populated'
            );
        }
         */

        foreach ($row as $key => $value) {
            $this->_values[$key] = $this->_inflateValue($key, $value);
        }

        $this->_isPopulated = true;
        return $this;
    }

    public function populateWithRawData($row)
    {
        if ($this->_isPopulated) {
            throw Exceptional::Runtime(
                'Record has already been populated'
            );
        }

        if ($row instanceof opal\query\IDataRowProvider) {
            $row = $row->toDataRowArray();
        } elseif ($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        }

        if (!is_array($row)) {
            throw Exceptional::InvalidArgument(
                'Could not populate record - input data cannot be converted to an array'
            );
        }


        // Inflate values from adapter
        $temp = $row;

        foreach (opal\schema\Introspector::getFieldProcessors($this->_adapter) as $name => $field) {
            $row[$name] = $field->inflateValueFromRow($name, $temp, $this);
        }


        // Inflate values from extension
        foreach ($row as $key => $value) {
            $this->_values[$key] = $this->_inflateValue($key, $value);
        }


        $this->_isPopulated = true;
        return $this;
    }



    public function shouldBypassHooks(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_bypassHooks = $flag;
            return $this;
        }

        return $this->_bypassHooks;
    }


    // Collection
    public function import(...$input)
    {
        foreach ($input as $row) {
            if ($row instanceof opal\query\IDataRowProvider) {
                $row = $row->toDataRowArray();
            }

            if (!core\collection\Util::isIterable($row)) {
                continue;
            }

            /*
            // Sanitize values from adapter
            $temp = $row;

            foreach(opal\schema\Introspector::getFieldProcessors($this->_adapter, array_keys($row)) as $name => $field) {
                if(isset($temp[$name])) {
                    $value = $temp[$name];
                } else {
                    $value = null;
                }

                $row[$name] = $field->sanitizeValue($value, $this);
            }

            // Sanitize values from extension
            foreach($row as $key => $value) {
                $this->_changes[$key] = $this->_sanitizeValue($key, $value);
            }
             */

            foreach ($row as $key => $value) {
                $this->offsetSet($key, $value);
            }
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->_values) && empty($this->_changes);
    }

    public function clear()
    {
        $this->_values = [];
        $this->_changes = [];
        $this->_isPopulated = false;

        return $this;
    }

    public function extract()
    {
        Glitch::incomplete();
    }

    public function count(): int
    {
        return count(array_merge($this->_values, $this->_changes));
    }

    public function toArray(): array
    {
        $output = [];

        foreach (array_merge($this->_values, $this->_changes) as $key => $value) {
            /*
            if($value instanceof IPreparedValueContainer && !$value->isPrepared()) {
                $value->prepareValue($this, $key);
            }
             */

            if ($value instanceof IValueContainer) {
                $value = $value->getValue();
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function extricate(string ...$keys): array
    {
        $output = [];

        foreach ($keys as $key) {
            $value = $this->_changes[$key] ?? $this->_values[$key] ?? null;

            /*
            if($value instanceof IPreparedValueContainer && !$value->isPrepared()) {
                $value->prepareValue($this, $key);
            }
             */

            if ($value instanceof IValueContainer) {
                $value = $value->getValue();
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function __toString(): string
    {
        return $this->getPrimaryKeySet()->__toString();
    }

    // Storage
    public function save(mesh\job\IQueue $queue = null)
    {
        $execute = false;

        if ($queue === null) {
            $execute = true;
            $queue = new mesh\job\Queue();
        }

        $this->deploySaveJobs($queue);

        if ($execute) {
            $queue->execute();
        }

        return $this;
    }

    public function delete(mesh\job\IQueue $queue = null)
    {
        $execute = false;

        if ($queue === null) {
            $execute = true;
            $queue = new mesh\job\Queue();
        }

        $this->deployDeleteJobs($queue);

        if ($execute) {
            $queue->execute();
        }

        return $this;
    }

    public function deploySaveJobs(mesh\job\IQueue $queue)
    {
        $recordJob = null;

        if ($queue->isDeployed($this)) {
            return $queue->getLastJobUsing($this);
        }

        if ($this->hasChanged() || $this->isNew()) {
            /** @phpstan-ignore-next-line */
            $recordJob = $queue->save($this);
            $ignored = false;
        } else {
            $queue->ignore($this);
            $ignored = true;
        }

        foreach (array_merge($this->_values, $this->_changes) as $key => $value) {
            if ($value instanceof IJobAwareValueContainer) {
                $value->deploySaveJobs($queue, $this, $key, $recordJob);
            }
        }

        if ($ignored) {
            $queue->unignore($this);
        }

        return $recordJob;
    }

    public function deployDeleteJobs(mesh\job\IQueue $queue)
    {
        $recordJob = null;

        if (!$this->isNew()) {
            if ($queue->hasJobUsing($this)) {
                return $recordJob;
            }

            /** @phpstan-ignore-next-line */
            $recordJob = $queue->delete($this);

            foreach (array_merge($this->_values, $this->_changes) as $key => $value) {
                if ($value instanceof IJobAwareValueContainer) {
                    $value->deployDeleteJobs($queue, $this, $key, $recordJob);
                }
            }
        }

        return $recordJob;
    }

    public function triggerJobEvent(mesh\job\IQueue $queue, IJob $job, $when)
    {
        $jobName = $job->getRecordJobName();
        $funcPrefix = $event = $meshManager = null;

        if ($when != IJob::EVENT_POST) {
            $funcPrefix = ucfirst($when);
        }

        $func = 'on' . $funcPrefix . $jobName;

        if (method_exists($this, $func)) {
            $this->{$func}($queue, $job);
        }

        $broadcast = static::BROADCAST_HOOK_EVENTS;

        if ($broadcast === null) {
            $broadcast = $this->_adapter->shouldRecordsBroadcastHookEvents();
        }

        if ($broadcast && !$this->_bypassHooks) {
            $event = new mesh\event\Event(
                $this,
                $funcPrefix . $jobName,
                null,
                $queue,
                $job
            );

            $meshManager = mesh\Manager::getInstance();
            $meshManager->emitEventObject($event);
        }

        if (in_array($jobName, ['Insert', 'Update', 'Replace'])) {
            $func = 'on' . $funcPrefix . 'Save';

            if (method_exists($this, $func)) {
                $this->{$func}($queue, $job);
            }

            /** @phpstan-ignore-next-line */
            if ($broadcast && !$this->_bypassHooks && $event && $meshManager) {
                $event->setAction($funcPrefix . 'Save');
                $meshManager->emitEventObject($event);
            }
        }

        return $this;
    }


    // Access
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    public function __get($key)
    {
        $output = null;

        if (array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } elseif (array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }

        if ($output instanceof IPreparedValueContainer && !$output->isPrepared()) {
            $output->prepareValue($this, $key);
        }

        return $this->_prepareValueForUser($key, $output);
    }

    public function getRaw($key)
    {
        $output = null;

        if (array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } elseif (array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }

        return $output;
    }

    public function getRawId($key)
    {
        $output = $this->getRaw($key);

        if ($output instanceof IIdProviderValueContainer) {
            $output = $output->getRawId();
        } elseif ($output instanceof IValueContainer) {
            $output = $output->getValue();
        }

        if ($output instanceof IPrimaryKeySet) {
            $output = $output->getRawValue();
        }

        return $output;
    }

    public function getOriginal($key)
    {
        $output = null;

        if (array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }

        return $this->_prepareOutputValue($key, $output);
    }


    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    public function forceSet($key, $value)
    {
        // Sanitize value from record
        $value = $this->_sanitizeValue($key, $value);

        // Sanitize value from extension
        if ($fieldProcessor = opal\schema\Introspector::getFieldProcessor($this->_adapter, $key)) {
            $value = $fieldProcessor->sanitizeValue($value, $this);
        }

        if (array_key_exists($key, $this->_changes)) {
            if ($this->_changes[$key] instanceof IValueContainer) {
                $this->_changes[$key]->setValue($value);
            } else {
                $this->_changes[$key] = $value;
            }
        } else {
            if (isset($this->_values[$key])
            && $this->_values[$key] instanceof IValueContainer) {
                $this->_values[$key]->setValue($value);
            } else {
                $this->_values[$key] = $value;
            }
        }

        return $this;
    }

    public function get($key, $default = null)
    {
        return $this->offsetGet($key, $default);
    }

    public function export($key, $default = null)
    {
        if ($key == '@primary') {
            return $this->getPrimaryKeySet();
        } else {
            $first = substr($key, 0, 1);

            if ($first == '#') {
                return $this->getRawId(substr($key, 1));
            } elseif ($first == '!') {
                return $this->getOriginal(substr($key, 1));
            } elseif ($first == '%') {
                return $this->getRaw(substr($key, 1));
            }
        }

        $output = null;

        if (array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } elseif (array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }

        if ($output === null) {
            $output = $default;
        }

        if ($output instanceof IValueContainer) {
            $output = $output->getValueForStorage();
        } elseif ($output instanceof IPrimaryKeySetProvider) {
            $output = $output->getPrimaryKeySet();
        }

        if ($output instanceof IPrimaryKeySet && $output->isNull()) {
            $output = null;
        }

        return $output;
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            if ($this->offsetExists($key)) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        // Sanitize value from record
        $value = $this->_sanitizeValue($key, $value);

        // Sanitize value from extension
        if ($fieldProcessor = opal\schema\Introspector::getFieldProcessor($this->_adapter, $key)) {
            $value = $fieldProcessor->sanitizeValue($value);
        }


        if (array_key_exists($key, $this->_changes)) {
            $oldVal = $this->_changes[$key];
        } else {
            $oldVal = null;
            $isEqual = null;

            if (isset($this->_values[$key])) {
                $oldVal = $this->_values[$key];
            }

            if ($oldVal instanceof IValueContainer) {
                if ($isEqual = $oldVal->eq($value)) {
                    return;
                }
            }


            if ($isEqual === null && $fieldProcessor) {
                if ($isEqual = $fieldProcessor->compareValues($oldVal, $value)) {
                    return;
                }
            }

            if ($isEqual === null && $oldVal === $value) {
                return;
            }

            if ($oldVal instanceof IValueContainer) {
                $oldVal = $oldVal->duplicateForChangeList();
            }

            $this->_changes[$key] = $oldVal;
        }

        if ($this->_changes[$key] instanceof IValueContainer) {
            if ($this->_changes[$key] instanceof IPreparedValueContainer) {
                $this->_changes[$key]->prepareToSetValue($this, $key);
            }

            $this->_changes[$key]->setValue($value);
        } else {
            $this->_changes[$key] = $value;
        }

        $this->onValueChange($key, $oldVal, $value);
    }

    public function offsetGet(mixed $key, $default = null): mixed
    {
        if ($key == '@primary') {
            return $this->getPrimaryKeySet();
        } else {
            $first = substr($key, 0, 1);

            if ($first == '#') {
                return $this->getRawId(substr($key, 1));
            } elseif ($first == '!') {
                return $this->getOriginal(substr($key, 1));
            } elseif ($first == '%') {
                return $this->getRaw(substr($key, 1));
            }
        }

        $output = null;

        if (array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } elseif (array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }

        return $this->_prepareOutputValue($key, $output, $default);
    }

    protected function _prepareOutputValue($key, $value, $default = null)
    {
        if ($value instanceof IValueContainer) {
            if ($value instanceof IPreparedValueContainer && !$value->isPrepared()) {
                $value->prepareValue($this, $key);
            }

            $value = $value->getValue($default);
        }

        $value = $this->_prepareValueForUser($key, $value);

        if ($value === null) {
            $value = $default;
        }

        return $value;
    }

    public function offsetExists(mixed $key): bool
    {
        if (array_key_exists($key, $this->_changes) && $this->_changes[$key] !== null) {
            if ($this->_changes[$key] instanceof IValueContainer) {
                if ($this->_changes[$key] instanceof IPreparedValueContainer && !$this->_changes[$key]->isPrepared()) {
                    $this->_changes[$key]->prepareValue($this, $key);
                }

                return $this->_changes[$key]->hasValue();
            } else {
                return true;
            }
        }

        if (array_key_exists($key, $this->_values) && $this->_values[$key] !== null) {
            if ($this->_values[$key] instanceof IValueContainer) {
                if ($this->_values[$key] instanceof IPreparedValueContainer && !$this->_values[$key]->isPrepared()) {
                    $this->_values[$key]->prepareValue($this, $key);
                }

                return $this->_values[$key]->hasValue();
            } else {
                return true;
            }
        }

        return false;
    }

    public function offsetUnset(mixed $key): void
    {
        $output = null;

        if (array_key_exists($key, $this->_changes)) {
            $output = $this->_changes[$key];
        } elseif (array_key_exists($key, $this->_values)) {
            $output = $this->_values[$key];
        }

        $this->_changes[$key] = null;
        //unset($this->_values[$key]);

        $this->onValueRemove($key, $output);
    }


    protected function _inflateValue($key, $value)
    {
        return $value;
    }

    protected function _deflateValue($key, $value)
    {
        return $value;
    }

    protected function _sanitizeValue($key, $value)
    {
        return $value;
    }

    protected function _prepareValueForUser($key, $value)
    {
        return $value;
    }


    protected function onValueChange($key, $oldValue, $newValue)
    {
    }
    protected function onValueRemove($key, $oldValue)
    {
    }



    // Mesh
    public function getEntityLocator()
    {
        if (!$this->_adapter instanceof mesh\entity\IParentEntity) {
            throw Exceptional::{'df/mesh/entity/NotFound'}(
                'Record adapter is not an entity handler'
            );
        }

        if ($this->_adapter instanceof mesh\entity\IActiveParentEntity) {
            return $this->_adapter->getSubEntityLocator($this);
        }

        $keySet = $this->getPrimaryKeySet();
        $id = $keySet->getEntityId();

        $output = $this->_adapter->getEntityLocator();
        $output->addNode(null, 'Record', $id);

        return $output;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $output = [];

        foreach (array_merge($this->_values, $this->_changes) as $key => $value) {
            if ($value instanceof IValueContainer) {
                $value = $value->getDumpValue();
            }

            if (array_key_exists($key, $this->_changes)) {
                if (!array_key_exists($key, $this->_values)) {
                    $key = '+ ' . $key;
                } elseif ($this->_changes[$key] === null) {
                    if (isset($this->_values[$key])) {
                        $value = $this->_values[$key];
                    }

                    if ($value !== null) {
                        $key = '- ' . $key;
                    }
                } else {
                    $key .= ' *';
                }
            }

            yield 'value:' . $key => $value;
        }
    }
}
