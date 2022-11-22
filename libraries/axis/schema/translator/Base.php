<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\translator;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;

use df\axis;
use df\opal;

abstract class Base implements axis\schema\ITranslator
{
    protected $_unit;
    protected $_axisSchema;
    protected $_targetSchema;
    protected $_isNew = true;

    public function __construct(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $axisSchema, opal\schema\ISchema $targetSchema = null)
    {
        $this->_unit = $unit;
        $this->_axisSchema = $axisSchema;
        $this->_isNew = true;

        if (!$targetSchema) {
            if ($this->_storageExists()) {
                $this->_isNew = false;
                $targetSchema = $this->_getTargetSchema();
            } else {
                $targetSchema = $this->_createTargetSchema();
            }
        } else {
            $this->_isNew = false;
        }

        $this->_targetSchema = $targetSchema;
    }

    abstract protected function _storageExists();
    abstract protected function _getTargetSchema();
    abstract protected function _createTargetSchema();

    public function getUnit()
    {
        return $this->_unit;
    }

    public function getAxisSchema()
    {
        return $this->_axisSchema;
    }

    public function getTargetSchema()
    {
        return $this->_targetSchema;
    }


    public function createFreshTargetSchema()
    {
        if (!$this->_isNew) {
            $this->_targetSchema = $this->_createTargetSchema();
            $this->_isNew = true;
        }

        $axisPrimaryIndex = $this->_axisSchema->getPrimaryIndex();
        $supportsIndexes = $this->_targetSchema instanceof opal\schema\IIndexProvider;

        // Add fields
        foreach ($this->_axisSchema->getFields() as $name => $axisField) {
            $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);

            if ($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach ($primitive->getPrimitives() as $name => $child) {
                    $this->_targetSchema->addPreparedField(
                        $this->_createField($child)
                    );
                }
            } elseif ($axisField instanceof opal\schema\INullPrimitiveField) {
                continue;
            } else {
                $this->_targetSchema->addPreparedField(
                    $this->_createField($primitive)
                );
            }
        }

        // Add indexes
        if ($supportsIndexes) {
            foreach ($this->_axisSchema->getIndexes() as $name => $axisIndex) {
                $isPrimary = $axisIndex === $axisPrimaryIndex;
                $targetIndex = null;

                foreach ($this->_createIndexes($axisIndex, $isPrimary) as $newIndex) {
                    $this->_targetSchema->addPreparedIndex($newIndex);

                    if (!$targetIndex) {
                        $targetIndex = $newIndex;
                    }
                }

                if ($isPrimary) {
                    $this->_targetSchema->setPrimaryIndex($targetIndex);
                }
            }
        }

        $this->_unit->customizeTranslatedSchema($this->_targetSchema);
        return $this->_targetSchema;
    }

    public function updateTargetSchema()
    {
        if ($this->_isNew) {
            return $this->createFreshTargetSchema();
        }

        $axisPrimaryIndex = $this->_axisSchema->getPrimaryIndex();
        $lastAxisPrimaryIndex = $this->_axisSchema->getLastPrimaryIndex();
        $targetPrimaryIndex = $this->_targetSchema->getPrimaryIndex();

        $supportsIndexes = $this->_targetSchema instanceof opal\schema\IIndexProvider;


        if (!$primaryIndexHasChanged = $this->_axisSchema->hasPrimaryIndexChanged()) {
            $lastAxisPrimaryIndex = $axisPrimaryIndex;
        }


        // Remove indexes
        if ($supportsIndexes) {
            foreach ($this->_axisSchema->getIndexesToRemove() as $name => $axisIndex) {
                if ($axisIndex->isSingleMultiPrimitiveField() && !$axisIndex->isUnique()) {
                    $fieldReferences = $axisIndex->getFieldReferences();
                    $axisField = $fieldReferences[0]->getField();
                    $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);

                    if ($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                        foreach ($primitive->getPrimitives() as $name => $child) {
                            $this->_targetSchema->removeIndex(
                                $this->_getIndexName($axisIndex, false, $child)
                            );
                        }

                        continue;
                    }
                }

                $this->_targetSchema->removeIndex(
                    $this->_getIndexName($axisIndex, $axisIndex === $lastAxisPrimaryIndex)
                );
            }
        }


        // Remove fields
        foreach ($this->_axisSchema->getFieldsToRemove() as $name => $axisField) {
            if ($axisField instanceof opal\schema\IMultiPrimitiveField) {
                foreach ($axisField->getPrimitiveFieldNames() as $name) {
                    $this->_targetSchema->removeField($name);
                }
            } elseif ($axisField instanceof opal\schema\INullPrimitiveField) {
                continue;
            } else {
                $this->_targetSchema->removeField($name);
            }
        }

        // Update fields
        foreach ($this->_axisSchema->getFieldsToUpdate() as $oldName => $axisField) {
            if ($axisField instanceof opal\schema\INullPrimitiveField) {
                continue;
            }

            $name = $axisField->getName();
            $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);
            $replacedField = $this->_axisSchema->getReplacedField($oldName);
            $replacedPrimitive = $replacedField ?
                $axisField->getReplacedPrimitive($this->_unit, $this->_axisSchema) :
                null;

            if ($replacedField && $replacedPrimitive) {
                if (get_class($replacedField) == get_class($axisField)
                && $replacedPrimitive instanceof opal\schema\IMultiFieldPrimitive
                && $primitive instanceof opal\schema\IMultiFieldPrimitive
                && count($replacedPrimitive->getPrimitives()) == count($primitive->getPrimitives())) {
                    $replacedInnerPrimitives = array_values($replacedPrimitive->getPrimitives());

                    foreach (array_values($primitive->getPrimitives()) as $i => $innerPrimitive) {
                        $innerPrimitiveName = $innerPrimitive->getName();
                        $replacedInnerPrimitiveName = $replacedInnerPrimitives[$i]->getName();

                        if ($innerPrimitiveName != $replacedInnerPrimitiveName
                        && $this->_targetSchema->hasField($replacedInnerPrimitiveName)) {
                            $this->_targetSchema->renameField(
                                $replacedInnerPrimitiveName,
                                $innerPrimitiveName
                            );
                        }

                        $this->_targetSchema->replacePreparedField(
                            $this->_createField($innerPrimitive)
                        );
                    }
                } elseif ($replacedPrimitive instanceof opal\schema\IMultiFieldPrimitive) {
                    Glitch::incomplete([$replacedPrimitive, $primitive]);
                } elseif ($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                    Glitch::incomplete([$replacedPrimitive, $primitive]);
                } else {
                    if ($oldName != $name && $this->_targetSchema->hasField($replacedPrimitive->getName())) {
                        $this->_targetSchema->renameField(
                            $replacedPrimitive->getName(),
                            $primitive->getName()
                        );
                    }

                    $this->_targetSchema->replacePreparedField(
                        $this->_createField($primitive)
                    );
                }
            } else {
                if ($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                    foreach ($primitive->getPrimitives() as $innerPrimitive) {
                        $this->_targetSchema->replacePreparedField(
                            $this->_createField($innerPrimitive)
                        );
                    }
                } else {
                    $this->_targetSchema->replacePreparedField(
                        $this->_createField($primitive)
                    );
                }
            }


            /*
            if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                $childPrimitives = $primitive->getPrimitives();
                $lastChild = $firstChild = array_shift($childPrimitives);

                $this->_targetSchema->replacePreparedField(
                    $this->_createField($firstChild)
                );

                foreach($childPrimitives as $child) {
                    $this->_targetSchema->addPreparedFieldAfter(
                        $lastChild->getName(), $this->_createField($child)
                    );

                    $lastChild = $child;
                }
            } else {
                $this->_targetSchema->replacePreparedField(
                    $this->_createField($primitive)
                );
            }
             */
        }

        // Add fields
        foreach ($this->_axisSchema->getFieldsToAdd() as $name => $axisField) {
            $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);

            if ($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach ($primitive->getPrimitives() as $name => $child) {
                    $this->_targetSchema->addPreparedField(
                        $this->_createField($child)
                    );
                }
            } elseif ($axisField instanceof opal\schema\INullPrimitiveField) {
                continue;
            } else {
                $this->_targetSchema->addPreparedField(
                    $this->_createField($primitive)
                );
            }
        }


        if ($supportsIndexes) {
            // Update indexes
            foreach ($this->_axisSchema->getIndexesToUpdate() as $name => $axisIndex) {
                $isPrimary = $axisIndex === $axisPrimaryIndex;
                $newSet = $this->_createIndexes($axisIndex, $isPrimary);

                $oldIndex = clone $axisIndex;
                $oldIndex->_setName($name);

                $oldSet = $this->_createIndexes($oldIndex, $isPrimary, true);

                foreach ($oldSet as $i => $oldInnerIndex) {
                    $oldName = $oldInnerIndex->getName();
                    $newName = $newSet[$i]->getName();

                    if ($oldName != $newName) {
                        $this->_targetSchema->renameIndex($oldName, $newName);
                    }
                }

                foreach ($newSet as $newIndex) {
                    $this->_targetSchema->replacePreparedIndex($newIndex);

                    if ($isPrimary) {
                        $this->_targetSchema->setPrimaryIndex($newIndex);
                    }
                }
            }


            // Add indexes
            foreach ($this->_axisSchema->getIndexesToAdd() as $name => $axisIndex) {
                $isPrimary = $axisIndex === $axisPrimaryIndex;
                $targetIndex = null;

                foreach ($this->_createIndexes($axisIndex, $isPrimary) as $newIndex) {
                    $this->_targetSchema->addPreparedIndex($newIndex);

                    if (!$targetIndex) {
                        $targetIndex = $newIndex;
                    }
                }

                if ($isPrimary) {
                    if ($targetPrimaryIndex) {
                        $newName = $this->_getIndexName($axisIndex, false);
                        $oldName = $targetPrimaryIndex->getName();

                        if ($newName != $oldName) {
                            $this->_targetSchema->renameIndex($oldName, $newName);
                        }
                    }

                    $this->_targetSchema->setPrimaryIndex($targetIndex);
                }
            }
        }

        return $this->_targetSchema;
    }

    // Primitives
    protected function _createField(opal\schema\IPrimitive $primitive)
    {
        $type = $primitive->getType();
        $func = '_create' . $type . 'Field';

        if (!method_exists($this, $func)) {
            throw Exceptional::{'df/axis/schema/Runtime'}(
                'Primitive ' . $type . ' is currently not supported by RDBMS based tables, for field ' . $primitive->getName()
            );
        }

        return $this->{$func}($primitive);
    }

    abstract protected function _createBinaryField(opal\schema\Primitive_Binary $primitive);
    abstract protected function _createBitField(opal\schema\Primitive_Bit $primitive);
    abstract protected function _createBlobField(opal\schema\Primitive_Blob $primitive);
    abstract protected function _createBooleanField(opal\schema\Primitive_Boolean $primitive);
    abstract protected function _createCharField(opal\schema\Primitive_Char $primitive);
    abstract protected function _createDataObjectField(opal\schema\Primitive_DataObject $primitive);
    abstract protected function _createDateField(opal\schema\Primitive_Date $primitive);
    abstract protected function _createDateTimeField(opal\schema\Primitive_DateTime $primitive);
    abstract protected function _createDecimalField(opal\schema\Primitive_Decimal $primitive);
    abstract protected function _createEnumField(opal\schema\Primitive_Enum $primitive);
    abstract protected function _createFloatField(opal\schema\Primitive_Float $primitive);
    abstract protected function _createGuidField(opal\schema\Primitive_Guid $primitive);
    abstract protected function _createIntegerField(opal\schema\Primitive_Integer $primitive);
    abstract protected function _createSetField(opal\schema\Primitive_Set $primitive);
    abstract protected function _createTextField(opal\schema\Primitive_Text $primitive);
    abstract protected function _createTimeField(opal\schema\Primitive_Time $primitive);
    abstract protected function _createTimestampField(opal\schema\Primitive_Timestamp $primitive);
    abstract protected function _createVarbinaryField(opal\schema\Primitive_Varbinary $primitive);
    abstract protected function _createVarcharField(opal\schema\Primitive_Varchar $primitive);
    abstract protected function _createYearField(opal\schema\Primitive_Year $primitive);



    // Indexes
    protected function _createIndexes(opal\schema\IIndex $axisIndex, $isPrimary, $forChanges = false)
    {
        $output = [];
        $fieldReferences = $axisIndex->getFieldReferences();

        if ($axisIndex->isSingleMultiPrimitiveField() && !$axisIndex->isUnique()) {
            $axisField = $fieldReferences[0]->getField();

            if ($forChanges) {
                $name = $axisField->getName();
                $oldName = $this->_axisSchema->getOriginalFieldNameFor($name);

                if ($field = $this->_axisSchema->getReplacedField($oldName)) {
                    $axisField = $field;
                }
            }

            $primitive = null;

            if ($forChanges) {
                $primitive = $axisField->getReplacedPrimitive($this->_unit, $this->_axisSchema);
            }

            if (!$primitive) {
                $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);
            }

            if ($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach ($primitive->getPrimitives() as $name => $child) {
                    $output[] = $this->_targetSchema->createIndex($this->_getIndexName($axisIndex, $isPrimary, $child), [])
                        ->addField(
                            $forChanges ?
                                $this->_targetSchema->getReplacedField($child->getName()) :
                                $this->_targetSchema->getField($child->getName()),
                            $fieldReferences[0]->getSize(),
                            $fieldReferences[0]->isDescending()
                        );
                }

                return $output;
            }
        }

        $targetIndex = $this->_targetSchema->createIndex($this->_getIndexName($axisIndex, $isPrimary), [])
            ->isUnique($axisIndex->isUnique());

        foreach ($fieldReferences as $ref) {
            $axisField = $ref->getField();
            $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);

            if ($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach ($primitive->getPrimitives() as $name => $child) {
                    $targetIndex->addField(
                        $this->_targetSchema->getField($child->getName()),
                        $ref->getSize(),
                        $ref->isDescending()
                    );
                }
            } elseif ($axisField instanceof opal\schema\INullPrimitiveField) {
                throw Exceptional::Logic(
                    'You cannot put indexes on NullPrimitive fields'
                );
            } else {
                if (!$indexField = $this->_targetSchema->getField($primitive->getName())) {
                    throw Exceptional::Logic(
                        'Unable to find index field ' . $primitive->getName()
                    );
                }

                $targetIndex->addField(
                    $indexField,
                    $ref->getSize(),
                    $ref->isDescending()
                );
            }
        }

        return [$targetIndex];
    }

    protected function _getIndexName(opal\schema\IIndex $axisIndex, $isPrimary, opal\schema\IPrimitive $primitive = null)
    {
        if ($primitive) {
            return 'idx_' . $primitive->getName();
        } else {
            return 'idx_' . $axisIndex->getName();
        }
    }
}
