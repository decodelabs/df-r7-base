<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;

use DecodeLabs\Exceptional;

/*
 * This type requires an inverse field and must lookup and match target table.
 * Key resides on Many side, null primitive
 */
class OneToMany extends Base implements axis\schema\IOneToManyField
{
    use axis\schema\TRelationField;
    use axis\schema\TInverseRelationField;
    use axis\schema\TTargetPrimaryFieldAwareRelationField;

    protected function _init($targetUnit, $targetField=null)
    {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }


    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null)
    {
        $value = null;

        if (isset($row[$key])) {
            $value = $row[$key];
        }

        if ($forRecord) {
            $output = $this->sanitizeValue(null, $forRecord);

            if (is_array($value)) {
                $output->populateList($value);
            }
        } else {
            $output = $value;
        }

        return $output;
    }

    public function deflateValue($value)
    {
        return null;
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord=null)
    {
        if ($forRecord) {
            $output = new axis\unit\table\record\InlineManyRelationValueContainer($this);
            $output->prepareToSetValue($forRecord, $this->_name);

            if (is_array($value)) {
                $output->addList($value);
            } elseif ($value !== null) {
                $output->add($value);
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
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false)
    {
        $localRelationManifest = $this->getLocalRelationManifest();

        if (!$localRelationManifest->isSingleField()) {
            throw Exceptional::Runtime(
                'Query clause on field '.$this->_name.' cannot be executed as it relies on a multi-field primary key. '.
                'You should probably use a fieldless join constraint instead'
            );
        }

        if (!$parent instanceof opal\query\ISourceProvider) {
            throw Exceptional::Logic(
                'Clause factory is not a source provider', null, $parent
            );
        }

        $sourceManager = $parent->getSourceManager();
        $source = $field->getSource();

        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId);
        $targetField = $sourceManager->extrapolateIntrinsicField($source, $source->getAlias().'.'.$localRelationManifest->getSingleFieldName());

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

    // Populate
    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate)
    {
        $output = opal\query\Initiator::beginAttachFromPopulate($populate);

        $parentSourceAlias = $populate->getParentSourceAlias();
        $targetSourceAlias = $populate->getSourceAlias();

        $output->on($targetSourceAlias.'.'.$this->_targetField, '=', $parentSourceAlias.'.@primary');
        $output->asMany($this->_name);

        return $output;
    }


    // Validate
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema)
    {
        $this->_sanitizeTargetUnitId($localUnit);

        return $this;
    }

    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $localSchema)
    {
        // Local
        $localPrimaryIndex = $this->_validateLocalPrimaryIndex($localUnit, $localSchema);


        // Target
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $targetField = $this->_validateInverseRelationField($targetUnit, $targetSchema);
        $this->_validateDefaultValue($localUnit);

        return $this;
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

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $def = $this->getFieldSchemaString();
        $arg = $this->_targetUnitId;

        if ($this->_targetField) {
            $arg .= ' -> '.$this->_targetField;
        }

        $def .= '('.$arg.')';

        yield 'definition' => $def;
    }
}
