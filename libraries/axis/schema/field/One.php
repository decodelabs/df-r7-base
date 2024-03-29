<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df\axis;
use df\opal;

/*
 * This type does not care about inverse at all.
 * Just return primary primitive
 */
class One extends Base implements axis\schema\IOneField
{
    use axis\schema\TRelationField;
    use axis\schema\TTargetPrimaryFieldAwareRelationField;


    protected function _init($targetTableUnit)
    {
        $this->setTargetUnitId($targetTableUnit);
    }


    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        $value = $this->getTargetRelationManifest()->extractFromRow($key, $row);

        if (!$forRecord) {
            // Only need a simple value
            if (array_key_exists($key, $row)) {
                return $row[$key];
            } else {
                if ($value === null) {
                    return null;
                }

                return $this->getTargetRelationManifest()->toPrimaryKeySet($value);
            }
        }

        // Need to build a value container
        return new axis\unit\table\record\OneRelationValueContainer(
            $this,
            $forRecord,
            $value
        );
    }

    public function deflateValue($value)
    {
        if ($value instanceof opal\record\IRecord) {
            $value = $value->getPrimaryKeySet();
        }

        if (!$value instanceof opal\record\IPrimaryKeySet) {
            $value = new opal\record\PrimaryKeySet($this->getTargetRelationManifest()->getPrimitiveFieldNames(), $value);
        }

        $output = [];
        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId);
        $schema = $targetUnit->getUnitSchema();

        foreach ($value->toArray() as $key => $value) {
            if ($field = $schema->getField($key)) {
                $value = $field->deflateValue($value);
            }

            $output[$this->_name . '_' . $key] = $value;
        }

        return $output;
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if (!$forRecord) {
            return $value;
        }

        return new axis\unit\table\record\OneRelationValueContainer(
            $this,
            $forRecord,
            $value
        );
    }

    public function generateInsertValue(array $row)
    {
        return null;
    }


    // Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr = false)
    {
        return opal\query\clause\Clause::mapVirtualClause(
            $parent,
            $field,
            $operator,
            $value,
            $isOr
        );
    }


    // Populate
    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate)
    {
        $output = opal\query\Initiator::beginAttachFromPopulate($populate);

        $parentSourceAlias = $populate->getParentSourceAlias();
        $targetSourceAlias = $populate->getSourceAlias();

        $output->on($targetSourceAlias . '.@primary', '=', $parentSourceAlias . '.' . $this->_name);
        $output->asOne($this->_name);

        return $output;
    }


    // Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema)
    {
        $this->_sanitizeTargetUnitId($localUnit);

        $oldName = $schema->getOriginalFieldNameFor($this->_name);

        if ($oldName != $this->_name && $schema->hasIndex($oldName)) {
            $schema->renameIndex($oldName, $this->_name);
        }

        if (!$schema->hasIndex($this->_name)) {
            $schema->addIndex($this->_name);
        } elseif ($schema->getReplacedField($oldName)) {
            if ($index = $schema->getIndex($oldName)) {
                $index->markAsChanged();
            }
        }

        return $this;
    }

    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema)
    {
        // Target
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $this->_validateDefaultValue($localUnit);

        return $this;
    }

    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetRelationManifest = $this->getTargetRelationManifest();
        $output = [];

        foreach ($targetRelationManifest as $fieldName => $primitive) {
            $field = $targetSchema->getField($fieldName);

            $dupField = $field->duplicateForRelation($targetUnit, $targetSchema);
            $dupField->_setName($this->_name . '_' . $dupField->getName());

            $output[] = $dupField;
        }

        return $output;
    }


    // Primitive
    public function getPrimitiveFieldNames()
    {
        return $this->getTargetRelationManifest()->getPrimitiveFieldNames($this->_name);
    }



    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setRelationStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getRelationStorageArray()
        );
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $def = $this->getFieldSchemaString();
        $def .= '(' . $this->_targetUnitId . ')';

        yield 'definition' => $def;
    }
}
