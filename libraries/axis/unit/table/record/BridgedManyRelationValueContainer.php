<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\unit\table\record;

use ArrayIterator;

use DecodeLabs\Exceptional;
use df\axis;
use df\core;
use df\mesh;

use df\opal;

class BridgedManyRelationValueContainer implements
    opal\record\IJobAwareValueContainer,
    opal\record\IPreparedValueContainer,
    opal\record\IManyRelationValueContainer,
    opal\record\IIdProviderValueContainer,
    core\IArrayProvider,
    \Countable,
    \IteratorAggregate,
    core\IDescribable
{
    protected $_current = [];
    protected $_new = [];
    protected $_remove = [];
    protected $_removeAll = false;

    protected $_localPrimaryKeySet;
    protected $_targetPrimaryKeySet;
    protected $_field;

    protected $_record = null;

    public function __construct(axis\schema\IBridgedRelationField $field)
    {
        $this->_field = $field;
        $this->_localPrimaryKeySet = $field->getLocalRelationManifest()->toPrimaryKeySet();
        $this->_targetPrimaryKeySet = $field->getTargetRelationManifest()->toPrimaryKeySet();
    }

    public function getOutputDescription(): ?string
    {
        return (string)$this->countAll();
    }

    public function isPrepared()
    {
        return $this->_record !== null;
    }

    public function prepareValue(opal\record\IRecord $record, $fieldName)
    {
        $this->_record = $record;
        $this->_localPrimaryKeySet->updateWith($record);

        return $this;
    }

    public function prepareToSetValue(opal\record\IRecord $record, $fieldName)
    {
        return $this->prepareValue($record, $fieldName);
    }

    public function setValue($value)
    {
        $this->removeAll();

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $id) {
            $this->add($id);
        }

        return $this;
    }

    public function getValue($default = null)
    {
        return $this;
    }

    public function hasValue(): bool
    {
        return !empty($this->_current) || !empty($this->_new);
    }

    public function getStringValue($default = ''): string
    {
        return $this->__toString();
    }

    public function getValueForStorage()
    {
        return null;
    }

    public function eq($value)
    {
        return false;
    }

    public function duplicateForChangeList()
    {
        return $this;
    }

    public function getNew()
    {
        return $this->_new;
    }

    public function getCurrent()
    {
        return $this->_current;
    }

    public function getRemoved()
    {
        return $this->_remove;
    }


    // Collection
    public function toArray(): array
    {
        return array_merge($this->_current, $this->_new);
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

    public function getPopulated()
    {
        return $this->_current;
    }


    // Key sets
    public function getLocalPrimaryKeySet()
    {
        return $this->_localPrimaryKeySet;
    }

    public function getTargetPrimaryKeySet()
    {
        return $this->_targetPrimaryKeySet;
    }


    // Records
    public function add(...$records)
    {
        return $this->addList($records);
    }

    public function addList(array $records)
    {
        foreach ($this->_normalizeInputRecordList($records) as $id => $record) {
            $this->_new[$id] = $record;
        }

        if ($this->_record) {
            $this->_record->markAsChanged($this->_field->getName());
        }

        return $this;
    }

    public function populate(...$records)
    {
        return $this->populateList($records);
    }

    public function populateList(array $records)
    {
        foreach ($this->_normalizeInputRecordList($records) as $id => $record) {
            $this->_current[$id] = $record;
        }

        return $this;
    }

    public function remove(...$records)
    {
        return $this->removeList($records);
    }

    public function removeList(array $records)
    {
        $bridgeUnit = $this->getBridgeUnit();
        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();

        foreach ($records as $record) {
            if ($record instanceof opal\record\IPartial) {
                $record->setRecordAdapter($bridgeUnit);
                $record[$bridgeLocalFieldName] = $this->_localPrimaryKeySet;
            }

            if ($record instanceof opal\record\IRecord) {
                $id = opal\record\Base::extractRecordId($record);
            } elseif ($record instanceof opal\record\IPrimaryKeySet) {
                $id = opal\record\Base::extractRecordId($record);
            } else {
                $record = $this->_targetPrimaryKeySet->duplicateWith($record);
                $id = opal\record\Base::extractRecordId($record);
            }

            if (isset($this->_new[$id])) {
                unset($this->_new[$id]);
            } elseif (isset($this->_current[$id])) {
                $this->_remove[$id] = $this->_current[$id];
                unset($this->_current[$id]);
            } else {
                $this->_remove[$id] = $record;
            }
        }

        if ($this->_record) {
            $this->_record->markAsChanged($this->_field->getName());
        }

        return $this;
    }

    public function removeAll()
    {
        $this->_new = [];
        $this->_current = [];
        $this->_removeAll = true;
        return $this;
    }


    protected function _normalizeInputRecordList(array $records)
    {
        $index = [];
        $lookupKeySets = [];

        $localUnit = $this->_record->getAdapter();
        $bridgeUnit = $this->getBridgeUnit();
        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();

        foreach ($records as $record) {
            if ($record instanceof opal\record\IPartial && $record->isBridge()) {
                $record->setRecordAdapter($bridgeUnit);
                $record[$bridgeLocalFieldName] = $this->_localPrimaryKeySet;
                $ks = $this->_targetPrimaryKeySet->duplicateWith($record[$this->_field->getBridgeTargetFieldName()]);
                $id = opal\record\Base::extractRecordId($ks);
                $lookupKeySets[$id] = $ks;
            } elseif ($record instanceof opal\record\IPrimaryKeySetProvider) {
                $id = opal\record\Base::extractRecordId($record);
                //$lookupKeySets[$id] = $record->getPrimaryKeySet();
            } elseif ($record instanceof opal\record\IPrimaryKeySet) {
                $id = opal\record\Base::extractRecordId($record);
                $lookupKeySets[$id] = $record;
            } else {
                $record = $this->_targetPrimaryKeySet->duplicateWith($record);
                $id = opal\record\Base::extractRecordId($record);
                $lookupKeySets[$id] = $record;
            }

            if (!isset($this->_current[$id])) {
                $index[(string)$id] = $record;
            }
        }

        if (!empty($lookupKeySets)) {
            $targetUnit = $this->getTargetUnit();

            $query = opal\query\Initiator::factory()
                ->beginSelect($this->_targetPrimaryKeySet->getFieldNames())
                ->from($targetUnit);

            foreach ($lookupKeySets as $keySet) {
                $clause = $query->beginOrWhereClause();

                foreach ($keySet->toArray() as $key => $value) {
                    $clause->where($key, '=', $value);
                }

                $clause->endClause();
            }

            $res = $query->toArray();

            foreach ($lookupKeySets as $id => $keySet) {
                if (empty($res)) {
                    unset($index[$id]);
                    continue;
                }

                $keys = $keySet->toArray();
                $found = false;

                foreach ($res as $row) {
                    foreach ($keys as $key => $value) {
                        if (!isset($row[$key]) || $row[$key] != $value) {
                            continue 2;
                        }
                    }

                    $found = true;
                    break;
                }

                if (!$found) {
                    unset($index[$id]);
                }
            }
        }

        return $index;
    }


    public function getBridgeUnit()
    {
        return axis\Model::loadUnitFromId($this->_field->getBridgeUnitId());
    }

    public function getTargetUnit()
    {
        return axis\Model::loadUnitFromId($this->_field->getTargetUnitId());
    }

    public function getBridgeLocalFieldName()
    {
        return $this->_field->getBridgeLocalFieldName();
    }

    public function getBridgeTargetFieldName()
    {
        return $this->_field->getBridgeTargetFieldName();
    }

    public function newRecord(array $values = null)
    {
        return $this->getTargetUnit()->newRecord($values);
    }

    public function newBridgeRecord(array $values = null)
    {
        return $this->getBridgeUnit()->newRecord($values);
    }


    // Query
    public function select(...$fields)
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $this->_localPrimaryKeySet->updateWith($this->_record);

        $localUnit = $this->_record->getAdapter();
        $targetUnit = $this->getTargetUnit();
        $bridgeUnit = $this->getBridgeUnit();

        $targetSourceAlias = $targetUnit->getCanonicalUnitName();

        $localFieldName = $this->_field->getName();
        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();
        $bridgeAlias = $bridgeUnit->getUnitName();

        if (false !== strpos($bridgeAlias, '(')) {
            $bridgeAlias = $bridgeLocalFieldName . 'Bridge';
        }

        return opal\query\Initiator::factory()
            ->beginSelect($fields)
            ->from($targetUnit, $targetSourceAlias)

            // Join bridge table as constraint
            ->join($bridgeUnit->getBridgeFieldNames($localFieldName, [$bridgeTargetFieldName]))
                ->from($bridgeUnit, $bridgeAlias)
                ->on($bridgeAlias . '.' . $bridgeTargetFieldName, '=', $targetSourceAlias . '.@primary')
                ->endJoin()

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias . '.' . $bridgeLocalFieldName, '=', $this->_localPrimaryKeySet);
    }

    public function selectDistinct(...$fields)
    {
        return $this->select(...$fields)->isDistinct(true);
    }

    public function selectFromBridge(...$fields)
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $this->_localPrimaryKeySet->updateWith($this->_record);

        $localUnit = $this->_record->getAdapter();
        $bridgeUnit = $this->getBridgeUnit();

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeAlias = $bridgeLocalFieldName . 'Bridge';

        return opal\query\Initiator::factory()
            ->beginSelect($fields)
            ->from($bridgeUnit, $bridgeAlias)

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias . '.' . $bridgeLocalFieldName, '=', $this->_localPrimaryKeySet);
    }

    public function selectDistinctFromBridge(...$fields)
    {
        return $this->selectFromBridge(...$fields)->isDistinct(true);
    }

    public function selectFromNew(...$fields)
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $this->_localPrimaryKeySet->updateWith($this->_record);

        $localUnit = $this->_record->getAdapter();
        $targetUnit = $this->getTargetUnit();
        $localFieldName = $this->_field->getName();

        return opal\query\Initiator::factory()
            ->beginSelect($fields)
            ->from($targetUnit, $localFieldName)
            ->wherePrerequisite('@primary', 'in', $this->_getKeySets($this->_new));
    }

    public function selectFromNewToBridge(...$fields)
    {
        $localUnit = $this->_record->getAdapter();
        $bridgeUnit = $this->getBridgeUnit();

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();
        $bridgeAlias = $bridgeUnit->getUnitName();

        if (false !== strpos($bridgeAlias, '(')) {
            $bridgeAlias = $bridgeLocalFieldName . 'Bridge';
        }

        return $this->selectFromNew(...$fields)
            ->whereCorrelation('@primary', '!in', $bridgeTargetFieldName)
                ->from($bridgeUnit, $bridgeAlias)
                ->where($bridgeAlias . '.' . $bridgeLocalFieldName, '=', $this->_localPrimaryKeySet)
                ->endCorrelation();
    }

    public function countAll()
    {
        return $this->select()->count();
    }

    public function countAllDistinct()
    {
        return $this->selectDistinct()->count();
    }

    public function countChanges()
    {
        $output = 0;
        $newKeys = $this->_getKeySets($this->_new);
        $removeKeys = $this->_getKeySets($this->_remove);
        $currentKeys = $this->getRelatedPrimaryKeys();

        foreach ($currentKeys as $id) {
            if (isset($newKeys[(string)$id])) {
                unset($newKeys[(string)$id]);
                continue;
            }

            if ($this->_removeAll) {
                $output++;
                continue;
            }

            if (isset($removeKeys[(string)$id])) {
                $output++;
            }
        }

        $output += count($newKeys);
        return $output;
    }

    public function fetch()
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $this->_localPrimaryKeySet->updateWith($this->_record);

        $localUnit = $this->_record->getAdapter();
        $targetUnit = $this->getTargetUnit();
        $bridgeUnit = $this->getBridgeUnit();

        $targetSourceAlias = $targetUnit->getCanonicalUnitName();

        $localFieldName = $this->_field->getName();
        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();
        $bridgeAlias = $bridgeUnit->getUnitName();

        if (false !== strpos($bridgeAlias, '(')) {
            $bridgeAlias = $bridgeLocalFieldName . 'Bridge';
        }

        return opal\query\Initiator::factory()
            ->beginFetch()
            ->from($targetUnit, $targetSourceAlias)

            // Join bridge table as constraint
            ->joinConstraint()
                ->from($bridgeUnit, $bridgeAlias)
                ->on($bridgeAlias . '.' . $bridgeTargetFieldName, '=', $targetSourceAlias . '.@primary')
                ->endJoin()

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias . '.' . $bridgeLocalFieldName, '=', $this->_localPrimaryKeySet);
    }

    public function fetchFromBridge()
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $this->_localPrimaryKeySet->updateWith($this->_record);

        $localUnit = $this->_record->getAdapter();
        $bridgeUnit = $this->getBridgeUnit();

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeAlias = $bridgeLocalFieldName . 'Bridge';

        return opal\query\Initiator::factory()
            ->beginFetch()
            ->from($bridgeUnit, $bridgeAlias)

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias . '.' . $bridgeLocalFieldName, '=', $this->_localPrimaryKeySet);
    }

    public function fetchBridgePartials()
    {
        $output = [];
        $bridgeUnit = $this->getBridgeUnit();

        foreach ($this->selectDistinctFromBridge() as $row) {
            $output[] = $bridgeUnit->newPartial($row);
        }

        return $output;
    }

    public function getRelatedPrimaryKeys()
    {
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();
        return $this->selectFromBridge($bridgeTargetFieldName)->toList($bridgeTargetFieldName);
    }

    public function getRawId()
    {
        $output = [];

        foreach ($this->getRelatedPrimaryKeys() as $key) {
            if ($key instanceof opal\record\IPrimaryKeySet) {
                $output[] = $key->getValue();
            } else {
                $output[] = $key;
            }
        }

        return $output;
    }

    public function getLiveIds()
    {
        $output = [];

        if (!$this->_removeAll) {
            $output = $this->getRawId();

            foreach ($this->_getKeySets($this->_remove) as $id) {
                if (($key = array_search($id, $output)) !== false) {
                    unset($output[$key]);
                }
            }
        }

        foreach ($this->_getKeySets($this->_new) as $id) {
            if ($id instanceof opal\record\IPrimaryKeySet) {
                $output[] = $id->getValue();
            } else {
                $output[] = $id;
            }
        }

        return array_unique($output);
    }

    protected function _getKeySets(array $records)
    {
        $keys = [];

        foreach ($records as $record) {
            if ($record instanceof opal\record\IPartial && $record->isBridge()) {
                $ks = $this->_targetPrimaryKeySet->duplicateWith($record[$this->_field->getBridgeTargetFieldName()]);
            } elseif ($record instanceof opal\record\IPrimaryKeySetProvider) {
                $ks = $record->getPrimaryKeySet();
            } elseif ($record instanceof opal\record\IPrimaryKeySet) {
                $ks = $record;
            } else {
                $ks = $this->_targetPrimaryKeySet->duplicateWith($record);
            }

            $keys[(string)$ks] = $ks;
        }

        return $keys;
    }



    // Tasks
    public function deploySaveJobs(mesh\job\IQueue $queue, opal\record\IRecord $parentRecord, $fieldName, mesh\job\IJob $recordJob = null)
    {
        $localUnit = $parentRecord->getAdapter();
        $this->_localPrimaryKeySet->updateWith($parentRecord);

        $targetUnit = $this->getTargetUnit();
        $bridgeUnit = $this->getBridgeUnit();

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();

        $removeAllJob = null;


        // Save any changed populated records
        /*
        foreach($this->_current as $id => $record) {
            if($record instanceof opal\record\IRecord) {
                $record->deploySaveJobs($queue);
            }
        }
         */



        // Remove all
        if ($this->_removeAll && !$this->_localPrimaryKeySet->isNull()) {
            $removeAllJob = new opal\query\job\DeleteKey($bridgeUnit, [
                $bridgeLocalFieldName => $this->_localPrimaryKeySet
            ]);

            $queue->addJob($removeAllJob);
        }

        $filterKeys = [];

        // Insert relation tasks
        foreach ($this->_new as $id => $record) {
            // Build bridge
            if ($record instanceof opal\record\IPartial && $record->isBridge()) {
                $bridgeRecord = $bridgeUnit->newRecord($record->toArray());
                /** @phpstan-ignore-next-line */
                $bridgeJob = $queue->replace($bridgeRecord);
            } else {
                $bridgeRecord = $bridgeUnit->newRecord();
                /** @phpstan-ignore-next-line */
                $bridgeJob = $queue->insert($bridgeRecord)->ifNotExists(true);
            }


            // Local ids
            $bridgeRecord->__set($bridgeLocalFieldName, $this->_localPrimaryKeySet);

            if ($recordJob) {
                $bridgeJob->addDependency(
                    $recordJob,
                    new opal\record\job\BridgeResolution($bridgeLocalFieldName)
                );
            }

            // Target key set
            if ($record instanceof opal\record\IPartial) {
                $targetKeySet = $this->_targetPrimaryKeySet->duplicateWith($record->get($bridgeTargetFieldName));
            } elseif ($record instanceof opal\record\IPrimaryKeySet) {
                $targetKeySet = $record;
            } else {
                $targetKeySet = $this->_targetPrimaryKeySet->duplicateWith($record);
            }

            // Filter remove all task
            if ($removeAllJob) {
                foreach ($targetKeySet->toArray() as $key => $value) {
                    $filterKeys[$bridgeTargetFieldName . '_' . $key][$id] = $value;
                }
            }


            // Target task
            $targetRecordJob = null;

            if ($record instanceof opal\record\IRecord) {
                $targetRecordJob = $record->deploySaveJobs($queue);
            }

            // Target ids
            $bridgeRecord->__set($bridgeTargetFieldName, $targetKeySet);

            if ($targetRecordJob) {
                $bridgeJob->addDependency(
                    $targetRecordJob,
                    new opal\record\job\BridgeResolution($bridgeTargetFieldName)
                );
            }

            if ($record instanceof opal\record\IPartial) {
                $bridgeRecord->import($record->getValuesForStorage());
            }


            // Remove-all dependency
            if ($removeAllJob) {
                $bridgeJob->addDependency($removeAllJob);
            }
        }

        if ($removeAllJob && !empty($filterKeys)) {
            $removeAllJob->setFilterKeys($filterKeys);
        }


        // Delete relation tasks
        if (!$removeAllJob && !empty($this->_remove) && !$this->_localPrimaryKeySet->isNull()) {
            foreach ($this->_remove as $id => $record) {
                $bridgeData = [];

                foreach ($this->_localPrimaryKeySet->toArray() as $key => $value) {
                    $bridgeData[$bridgeLocalFieldName . '_' . $key] = $value;
                }

                if ($record instanceof opal\record\IPrimaryKeySet) {
                    $targetKeySet = $record;
                } else {
                    $targetKeySet = $this->_targetPrimaryKeySet->duplicateWith($record);
                }

                foreach ($targetKeySet->toArray() as $key => $value) {
                    $bridgeData[$bridgeTargetFieldName . '_' . $key] = $value;
                }

                if (empty($bridgeData)) {
                    continue;
                }

                $queue->addJob(new opal\query\job\DeleteKey($bridgeUnit, $bridgeData));
            }
        }

        return $this;
    }

    public function acceptSaveJobChanges(opal\record\IRecord $record)
    {
        $this->_current = array_merge($this->_current, $this->_new);
        $this->_new = [];
        $this->_remove = [];
        $this->_removeAll = false;

        return $this;
    }

    public function deployDeleteJobs(mesh\job\IQueue $queue, opal\record\IRecord $parentRecord, $fieldName, mesh\job\IJob $recordJob = null)
    {
        if (!$recordJob) {
            return $this;
        }

        $localUnit = $parentRecord->getAdapter();
        $this->_localPrimaryKeySet->updateWith($parentRecord);
        $bridgeUnit = $this->getBridgeUnit();

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeData = [];

        foreach ($this->_localPrimaryKeySet->toArray() as $key => $value) {
            $bridgeData[$bridgeLocalFieldName . '_' . $key] = $value;
        }

        if (!empty($bridgeData)) {
            $queue->addJob($bridgeJob = new opal\query\job\DeleteKey($bridgeUnit, $bridgeData));
            $bridgeJob->addDependency($recordJob);
        }

        return $this;
    }

    public function acceptDeleteJobChanges(opal\record\IRecord $record)
    {
        $this->_new = array_merge($this->_current, $this->_new);
        $this->_current = [];
        $this->_remove = [];

        return $this;
    }


    public function populateInverse(array $inverse)
    {
        $this->_new = array_merge($this->_new, $this->_normalizeInputRecordList($inverse));
        return $this;
    }

    public function __toString(): string
    {
        return (string)count($this);
    }


    // Dump
    public function getDumpValue()
    {
        if (empty($this->_current) && empty($this->_new) && empty($this->_remove)) {
            return implode(', ', $this->_localPrimaryKeySet->getFieldNames()) .
            ' -> [' . implode(', ', $this->_targetPrimaryKeySet->getFieldNames()) . ']';
        }

        $output = $this->_current;

        foreach ($this->_new as $id => $record) {
            $output['+ ' . $id] = $record;
        }

        foreach ($this->_remove as $id => $record) {
            $output['- ' . $id] = $record;
        }

        return $output;
    }
}
