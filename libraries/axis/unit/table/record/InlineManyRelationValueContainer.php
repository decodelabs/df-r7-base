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

class InlineManyRelationValueContainer implements
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

    protected $_targetPrimaryKeySet;
    protected $_field;
    protected $_record = null;

    public function __construct(axis\schema\IOneToManyField $field)
    {
        $this->_field = $field;
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

    public function populateInverse(opal\record\IRecord $record = null)
    {
        if ($record) {
            $id = opal\record\Base::extractRecordId($record);
            $this->_new[$id] = $record;

            if ($this->_record) {
                $this->_record->markAsChanged($this->_field->getName());
            }
        }

        return $this;
    }


    public function getTargetUnitId()
    {
        return $this->_field->getTargetUnitId();
    }

    public function getTargetUnit()
    {
        return axis\Model::loadUnitFromId($this->_field->getTargetUnitId());
    }

    public function newRecord(array $values = null)
    {
        return $this->getTargetUnit()->newRecord($values);
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


    // Records
    public function add(...$records)
    {
        return $this->addList($records);
    }

    public function addList(array $records)
    {
        $targetField = $this->_field->getTargetField();

        foreach ($this->_normalizeInputRecordList($records) as $id => $record) {
            $this->_new[$id] = $record;

            if ($this->_record && $record instanceof opal\record\IRecord) {
                $record->markAsChanged($targetField);
                $record->getRaw($targetField)->setValue($this->_record);
            }
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

    protected function _normalizeInputRecordList(array $records)
    {
        $index = [];
        $lookupKeySets = [];

        foreach ($records as $record) {
            if ($record instanceof opal\record\IPrimaryKeySetProvider) {
                $id = opal\record\Base::extractRecordId($record);
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
            $localUnit = $this->_record->getAdapter();
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

    public function remove(...$records)
    {
        return $this->removeList($records);
    }

    public function removeList(array $records)
    {
        $index = [];

        foreach ($records as $record) {
            if ($record instanceof opal\record\IPrimaryKeySetProvider) {
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


    // Query
    public function select(...$fields)
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $localUnit = $this->_record->getAdapter();
        $localSchema = $localUnit->getUnitSchema();
        $targetUnit = $this->getTargetUnit();
        $targetSourceAlias = $targetUnit->getCanonicalUnitName();

        // Init query
        $query = opal\query\Initiator::factory()
            ->beginSelect($fields)
            ->from($targetUnit, $targetSourceAlias);


        $primaryKeySet = $this->_record->getPrimaryKeySet();
        $query->wherePrerequisite($targetSourceAlias . '.' . $this->_field->getTargetField(), '=', $primaryKeySet);

        return $query;
    }

    public function selectDistinct(...$fields)
    {
        return $this->select(...$fields)->isDistinct(true);
    }

    public function selectFromNew(...$fields)
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $localUnit = $this->_record->getAdapter();
        $targetUnit = $this->getTargetUnit();
        $localFieldName = $this->_field->getName();

        return opal\query\Initiator::factory()
            ->beginSelect($fields)
            ->from($targetUnit, $localFieldName)
            ->wherePrerequisite('@primary', 'in', $this->_getKeySets($this->_new));
    }

    public function countAll()
    {
        return $this->select()->count();
    }

    public function countAllDistinct()
    {
        return $this->selectDistinct()->count();
    }

    public function fetch()
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $localUnit = $this->_record->getAdapter();
        $localSchema = $localUnit->getUnitSchema();
        $targetUnit = $this->getTargetUnit();
        $targetSourceAlias = $targetUnit->getCanonicalUnitName();

        // Init query
        $query = opal\query\Initiator::factory()
            ->beginFetch()
            ->from($targetUnit, $targetSourceAlias);

        $primaryKeySet = $this->_record->getPrimaryKeySet();
        $query->wherePrerequisite($targetSourceAlias . '.' . $this->_field->getTargetField(), '=', $primaryKeySet);

        return $query;
    }

    public function getRelatedPrimaryKeys()
    {
        if (!$this->_record) {
            throw Exceptional::{'df/opal/record/ValuePreparation,Runtime'}(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        return $this->select('@primary')->toList('@primary');
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
        $targetUnit = $this->getTargetUnit();
        $targetField = $this->_field->getTargetField();
        $parentKeySet = $parentRecord->getPrimaryKeySet();


        // Save any changed populated records
        /*
        foreach($this->_current as $id => $record) {
            if($record instanceof opal\record\IRecord) {
                $record->deploySaveJobs($queue);
            }
        }
         */


        // Remove all
        $removeAllJob = null;

        if ($this->_removeAll && !$parentRecord->isNew()) {
            $removeAllJob = $queue->asap(
                'rmRel:' . opal\record\Base::extractRecordId($parentRecord) . '/' . $this->_field->getName(),
                $query = $targetUnit->update([$targetField => null])
                    ->where($targetField, '=', $parentKeySet)
                    ->chainIf(!empty($this->_new), function ($query) {
                        $query->where('@primary', '!in', $this->_new);
                    })
            );
        }


        // Insert relation tasks
        foreach ($this->_new as $id => $record) {
            if ($record instanceof opal\record\IPrimaryKeySet) {
                $targetKeySet = $record;
            } else {
                $targetKeySet = $this->_targetPrimaryKeySet->duplicateWith($record);
            }

            if ($record instanceof opal\record\IRecord) {
                if (!$targetRecordJob = $record->deploySaveJobs($queue)) {
                    /** @phpstan-ignore-next-line */
                    $targetRecordJob = $queue->update($record);
                }

                if ($recordJob && $parentRecord->isNew()) {
                    $recordJob->addDependency(
                        $targetRecordJob,
                        new opal\record\job\InsertResolution($targetField, true)
                    );
                } else {
                    $record->set($targetField, $parentKeySet);
                }
            } else {
                $values = [];

                foreach ($parentKeySet->toArray() as $key => $value) {
                    $values[$targetField . '_' . $key] = $value;
                }

                $targetRecordJob = $queue->asap(new opal\query\job\Update(
                    $targetUnit,
                    $record,
                    $values
                ));

                if ($recordJob) {
                    $targetRecordJob->addDependency(
                        $recordJob,
                        new opal\query\job\RawKeySetResolution($targetField)
                    );
                }
            }
        }


        // Remove relation tasks
        if (!empty($this->_remove) && !$removeAllJob) {
            $fields = [];

            foreach ($parentKeySet->toArray() as $key => $value) {
                $fields[] = $targetField . '_' . $key;
            }

            $nullKeySet = new opal\record\PrimaryKeySet($fields, null);

            foreach ($this->_remove as $id => $record) {
                if ($record instanceof opal\record\IPrimaryKeySet) {
                    $targetKeySet = $record;
                } else {
                    $targetKeySet = $this->_targetPrimaryKeySet->duplicateWith($record);
                }

                if ($record instanceof opal\record\IRecord) {
                    $record->set($targetField, $nullKeySet);
                    $record->deploySaveJobs($queue);
                } else {
                    $targetRecordJob = new opal\query\job\Update(
                        $targetUnit,
                        $record,
                        $nullKeySet->toArray()
                    );

                    $queue->addJob($targetRecordJob);
                }
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
        $localUnit = $parentRecord->getAdapter();
        $targetUnit = $this->getTargetUnit();
        $targetField = $this->_field->getTargetField();
        $targetSchema = $targetUnit->getUnitSchema();
        $parentKeySet = $parentRecord->getPrimaryKeySet();
        $values = [];

        foreach ($parentKeySet->toArray() as $key => $value) {
            $values[$targetField . '_' . $key] = $value;
        }

        $inverseKeySet = new opal\record\PrimaryKeySet(array_keys($values), $values);
        $primaryIndex = $targetSchema->getPrimaryIndex();

        if ($primaryIndex->hasField($targetSchema->getField($targetField))) {
            $targetRecordJob = new opal\query\job\DeleteKey(
                $targetUnit,
                $values
            );
        } else {
            $targetRecordJob = new opal\query\job\Update(
                $targetUnit,
                $inverseKeySet,
                $inverseKeySet->duplicateWith(null)->toArray()
            );
        }

        if (!$queue->hasJob($targetRecordJob)) {
            $queue->addJob($targetRecordJob);

            if ($recordJob) {
                //$recordJob->addDependency($targetRecordJob);
                $targetRecordJob->addDependency($recordJob);
            }
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

    public function __toString(): string
    {
        return (string)count($this);
    }



    // Dump
    public function getDumpValue()
    {
        if (empty($this->_current) && empty($this->_new) && empty($this->_remove)) {
            return '[' . $this->_field->getTargetField() . ']';
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
