<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\record;

use df;
use df\core;
use df\axis;
use df\opal;
use df\mesh;

use DecodeLabs\Glitch\Dumpable;

class OneChildRelationValueContainer implements
    opal\record\IJobAwareValueContainer,
    opal\record\IPreparedValueContainer,
    opal\record\IIdProviderValueContainer,
    Dumpable
{
    protected $_insertPrimaryKeySet;
    protected $_record = false;
    protected $_field;

    public function __construct(axis\schema\IOneChildField $field)
    {
        $this->_field = $field;
    }

    public function getTargetUnitId()
    {
        return $this->_field->getTargetUnitId();
    }

    public function getTargetUnit()
    {
        return axis\Model::loadUnitFromId($this->_field->getTargetUnitId());
    }

    public function newRecord(array $values=null)
    {
        return $this->getTargetUnit()->newRecord($values);
    }

    public function isPrepared()
    {
        return $this->_record !== false;
    }

    public function prepareValue(opal\record\IRecord $record, $fieldName)
    {
        $localUnit = $record->getAdapter();
        $targetUnit = $this->getTargetUnit();
        $query = $targetUnit->fetch();

        if ($this->_insertPrimaryKeySet) {
            foreach ($this->_insertPrimaryKeySet->toArray() as $field => $value) {
                $query->where($field, '=', $value);
            }
        } else {
            $query->where($this->_field->getTargetField(), '=', $record->getPrimaryKeySet());
        }

        $this->_record = $query->toRow();

        if ($this->_record) {
            $inverseValue = $this->_record->getRaw($this->_field->getTargetField());
            $inverseValue->populateInverse($record);
            $this->_record->markAsChanged($this->_field->getTargetField());
        }

        return $this;
    }

    public function prepareToSetValue(opal\record\IRecord $record, $fieldName)
    {
        return $this;
    }

    public function eq($value)
    {
        if (!$this->_record) {
            return false;
        }

        if ($value instanceof self) {
            $value = $value->getValue();
        }

        if ($value instanceof opal\record\IPrimaryKeySetProvider) {
            $value = $value->getPrimaryKeySet();
        } elseif (!$value instanceof opal\record\IPrimaryKeySet) {
            return false;
        }

        return $this->_record->getPrimaryKeySet()->eq($value);
    }

    public function setValue($value)
    {
        $record = false;

        if ($value instanceof self) {
            $record = $value->_record;
            $value = $record->getPrimaryKeySet();
        } elseif ($value instanceof opal\record\IPrimaryKeySetProvider) {
            $record = $value;
            $value = $value->getPrimaryKeySet();
        } elseif (!$value instanceof opal\record\IPrimaryKeySet) {
            // TODO: swap array('id') for target primary fields
            $value = new opal\record\PrimaryKeySet(['id'], ['id' => $value]);
        }

        $this->_insertPrimaryKeySet = $value;
        $this->_record = $record;

        return $this;
    }

    public function getValue($default=null)
    {
        if ($this->_record !== false) {
            return $this->_record;
        }

        return $default;
    }

    public function hasValue(): bool
    {
        return $this->_record !== false && $this->_record !== null;
    }

    public function getStringValue($default=''): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        return (string)$this->getRawId();
    }

    public function getValueForStorage()
    {
        return null;
    }

    public function getRawId()
    {
        if ($this->_record) {
            return $this->_record->getPrimaryKeySet()->getValue();
        }

        return null;
    }

    public function duplicateForChangeList()
    {
        $output = new self($this->_field);
        $output->_insertPrimaryKeySet = $this->_insertPrimaryKeySet;
        return $output;
    }

    public function populateInverse(opal\record\IRecord $record=null)
    {
        if (!$this->_insertPrimaryKeySet) {
            $this->_record = $record;
        }

        return $this;
    }


    // Tasks
    public function deploySaveJobs(mesh\job\IQueue $queue, opal\record\IRecord $record, $fieldName, mesh\job\IJob $recordJob=null)
    {
        if ($this->_insertPrimaryKeySet) {
            if (!$this->_record instanceof opal\record\IRecord) {
                $this->prepareValue($record, $fieldName);
            }

            $originalRecord = null;
            $targetField = $this->_field->getTargetField();

            if (!$record->isNew()) {
                $localUnit = $record->getAdapter();
                $targetUnit = axis\Model::loadUnitFromId($this->_field->getTargetUnitId());

                $query = $targetUnit->fetch();

                foreach ($record->getPrimaryKeySet()->toArray() as $field => $value) {
                    $query->where($targetField.'_'.$field, '=', $value);
                }

                $originalRecord = $query->toRow();
            }

            if (!$this->_record) {
                $this->_insertPrimaryKeySet->updateWith(null);
            } else {
                $targetRecordJob = $this->_record->deploySaveJobs($queue);

                if ($recordJob && $targetRecordJob) {
                    $recordJob->addDependency(
                        $targetRecordJob,
                        new opal\record\job\InsertResolution($targetField, true)
                    );
                } elseif (!$this->_insertPrimaryKeySet->isNull()) {
                    $this->_record->set($targetField, $this->_insertPrimaryKeySet);
                }
            }

            if ($originalRecord) {
                $originalRecord->set($targetField, null);
                $queue->save($originalRecord);
            }
        }

        return $this;
    }

    public function acceptSaveJobChanges(opal\record\IRecord $record)
    {
        return $this;
    }

    public function deployDeleteJobs(mesh\job\IQueue $queue, opal\record\IRecord $parentRecord, $fieldName, mesh\job\IJob $recordJob=null)
    {
        $localUnit = $parentRecord->getAdapter();
        $targetUnit = $this->getTargetUnit();
        $targetField = $this->_field->getTargetField();
        $targetSchema = $targetUnit->getUnitSchema();
        $parentKeySet = $parentRecord->getPrimaryKeySet();
        $values = [];

        foreach ($parentKeySet->toArray() as $key => $value) {
            $values[$targetField.'_'.$key] = $value;
        }

        $inverseKeySet = new opal\record\PrimaryKeySet(array_keys($values), $values);
        $primaryIndex = $targetSchema->getPrimaryIndex();

        if ($primaryIndex->hasField($targetSchema->getField($targetField))) {
            $targetRecordJob = new opal\query\job\DeleteKey(
                $targetUnit, $values
            );
        } else {
            $targetRecordJob = new opal\query\job\Update(
                $targetUnit, $inverseKeySet, $inverseKeySet->duplicateWith(null)->toArray()
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
        return $this;
    }


    // Dump
    public function getDumpValue()
    {
        if ($this->_record) {
            return $this->_record;
        }

        if ($this->_insertPrimaryKeySet) {
            if ($this->_insertPrimaryKeySet->countFields() == 1) {
                return $this->_insertPrimaryKeySet->getFirstKeyValue();
            }

            return $this->_insertPrimaryKeySet;
        }

        return '['.$this->_field->getTargetUnitId().']';
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if ($this->_record) {
            yield 'value' => $this->_record;
            return;
        }


        $output = $this->_field->getTargetUnitId();

        if ($this->_insertPrimaryKeySet) {
            $output .= ' : ';

            if ($this->_insertPrimaryKeySet->countFields() == 1) {
                $value = $this->_insertPrimaryKeySet->getFirstKeyValue();

                if ($value === null) {
                    $output .= 'null';
                } else {
                    $output .= $value;
                }
            } else {
                $t = [];

                foreach ($this->_insertPrimaryKeySet->toArray() as $key => $value) {
                    $valString = $key.'=';

                    if ($value === null) {
                        $valString .= 'null';
                    } else {
                        $valString .= $value;
                    }

                    $t[] = $valString;
                }

                $output .= implode(', ', $t);
            }
        } else {
            $output = '['.$output.']';
        }

        yield 'definition' => $output;
    }
}
