<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use DecodeLabs\Exceptional;
use df\axis;

use df\opal;

class OneChild extends Base implements axis\schema\IOneChildField
{
    use axis\schema\TRelationField;
    use axis\schema\TInverseRelationField;

    protected function _init($targetUnit, $targetField = null)
    {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }


    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        return $this->sanitizeValue(null, $forRecord);
    }

    public function deflateValue($value)
    {
        return null;
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if ($forRecord) {
            $output = new axis\unit\table\record\OneChildRelationValueContainer($this);

            if ($value !== null) {
                $output->setValue($value);
            }

            return $output;
        } else {
            return $value;
        }
    }

    public function generateInsertValue(array $row)
    {
        return null;
    }


    // Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr = false)
    {
        $localRelationManifest = $this->getLocalRelationManifest();

        if (!$localRelationManifest->isSingleField()) {
            throw Exceptional::Runtime(
                'Query clause on field ' . $this->_name . ' cannot be executed as it relies on a multi-field primary key. ' .
                'You should probably use a fieldless join constraint instead'
            );
        }

        if (!$parent instanceof opal\query\ISourceProvider) {
            throw Exceptional::Logic(
                'Clause factory is not a source provider',
                null,
                $parent
            );
        }

        $sourceManager = $parent->getSourceManager();
        $source = $field->getSource();

        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId);
        $targetField = $sourceManager->extrapolateIntrinsicField($source, $source->getAlias() . '.' . $localRelationManifest->getSingleFieldName());

        $mainOperator = 'in';

        if (opal\query\clause\Clause::isNegatedOperator($operator)) {
            $mainOperator = '!in';
            $operator = opal\query\clause\Clause::negateOperator($operator);
        } else {
            $operator = opal\query\clause\Clause::normalizeOperator($operator);
        }

        $query = opal\query\Initiator::factory()
            ->beginCorrelation($parent, $this->_targetField, 'id')
            ->from($targetUnit, $this->_name);

        if ($value === null) {
            $mainOperator = opal\query\clause\Clause::negateOperator($mainOperator);
        } else {
            $query->where('@primary', $operator, $value);
        }

        return opal\query\clause\Clause::factory(
            $parent,
            $targetField,
            $mainOperator,
            $query,
            $isOr
        );
    }

    protected $_localRelationManifest;

    public function getLocalRelationManifest()
    {
        if (!$this->_localRelationManifest) {
            $schema = $this->getTargetUnit()->getTransientUnitSchema();
            $this->_localRelationManifest = $schema->getField($this->_targetField)->getTargetRelationManifest();
        }

        return $this->_localRelationManifest;
    }


    // Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema)
    {
        $this->_sanitizeTargetUnitId($localUnit);

        return $this;
    }

    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema)
    {
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $targetField = $this->_validateInverseRelationField($targetUnit, $targetSchema);
    }


    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setRelationStorageArray($data);
        $this->_setInverseRelationStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getRelationStorageArray(),
            $this->_getInverseRelationStorageArray()
        );
    }
}
