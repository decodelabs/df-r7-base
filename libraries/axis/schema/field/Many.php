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

use DecodeLabs\Glitch;

/*
 * This type, like One does not care about inverses.
 * A relation table is always required
 * Should return a null primitive as key will always be on relation table
 */
class Many extends Base implements axis\schema\IManyField
{
    use axis\schema\TRelationField;
    use axis\schema\TBridgedRelationField;

    public function __construct(axis\schema\ISchema $schema, $type, $name, $args=null)
    {
        parent::__construct($schema, $type, $name, $args);
        $this->_bridgeUnitId = $this->_getBridgeUnitType().'('.$schema->getName().'.'.$this->_name.')';
    }

    protected function _init($targetTableUnit)
    {
        $this->setTargetUnitId($targetTableUnit);
    }


    public function isDominant(bool $flag=null)
    {
        return true;
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
            $output = new axis\unit\table\record\BridgedManyRelationValueContainer($this);
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
            throw new axis\schema\RuntimeException(
                'Query clause on field '.$this->_name.' cannot be executed as it relies on a multi-field primary key. '.
                'You should probably use a fieldless join constraint instead'
            );
        }

        if (!$parent instanceof opal\query\ISourceProvider) {
            throw Glitch::ELogic('Clause factory is not a source provider', null, $parent);
        }

        $sourceManager = $parent->getSourceManager();
        $source = $field->getSource();
        $localUnit = $source->getAdapter();

        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId);
        $targetField = $sourceManager->extrapolateIntrinsicField($source, $source->getAlias().'.'.$localRelationManifest->getSingleFieldName());

        $bridgeUnit = axis\Model::loadUnitFromId($this->_bridgeUnitId);

        $localFieldName = $this->getBridgeLocalFieldName();
        $targetFieldName = $this->getBridgeTargetFieldName();

        $query = opal\query\Initiator::factory()
            ->beginCorrelation($parent, $localFieldName, 'id')
            ->from($bridgeUnit, $localFieldName.'Bridge');

        $mainOperator = 'in';

        if (opal\query\clause\Clause::isNegatedOperator($operator)) {
            $mainOperator = '!in';
            $operator = opal\query\clause\Clause::negateOperator($operator);
        } else {
            $operator = opal\query\clause\Clause::normalizeOperator($operator);
        }

        $targetRelationManifest = $this->getTargetRelationManifest();
        $targetPrimaryKeySet = $targetRelationManifest->toPrimaryKeySet();

        switch ($operator) {
            case opal\query\clause\Clause::OP_IN:
                if ($targetRelationManifest->isSingleField()) {
                    $query->where($targetFieldName, $operator, $value);
                } else {
                    foreach ($value as $inVal) {
                        $query->orWhere($targetFieldName, '=', $targetPrimaryKeySet->duplicateWith($inVal));
                    }
                }

                break;

            default:
                $query->where($targetFieldName, $operator, $targetPrimaryKeySet->duplicateWith($value));
                break;
        }

        return opal\query\clause\Clause::factory(
            $parent,
            $targetField,
            $mainOperator,
            $query,
            $isOr
        );
    }


    // Populate
    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate)
    {
        $parentSource = $populate->getParentSource();
        $parentSourceAlias = $parentSource->getAlias();

        $targetSource = $populate->getSource();
        $targetSourceAlias = $targetSource->getAlias();

        $bridgeLocalFieldName = $this->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->getBridgeTargetFieldName();

        $bridgeUnit = axis\Model::loadUnitFromId($this->_bridgeUnitId);
        $bridgeSourceAlias = $populate->getFieldName().'_bridge';

        $output = opal\query\Initiator::beginAttachFromPopulate($populate);

        if ($populate->isSelect()) {
            $output->rightJoin($bridgeUnit->getBridgeFieldNames($this->_name, [$bridgeLocalFieldName, $bridgeTargetFieldName]))
                    ->from($bridgeUnit, $bridgeSourceAlias)
                    ->on($bridgeSourceAlias.'.'.$bridgeTargetFieldName, '=', $targetSourceAlias.'.@primary')
                    ->endJoin()
                ->on($bridgeSourceAlias.'.'.$bridgeLocalFieldName, '=', $parentSourceAlias.'.@primary');
        } else {
            $output->rightJoinConstraint()
                    ->from($bridgeUnit, $bridgeSourceAlias)
                    ->on($bridgeSourceAlias.'.'.$bridgeTargetFieldName, '=', $targetSourceAlias.'.@primary')
                    ->endJoin()
                ->on($bridgeSourceAlias.'.'.$bridgeLocalFieldName, '=', $parentSourceAlias.'.@primary');
        }

        $output->asMany($this->_name);

        return $output;
    }


    // Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $localSchema)
    {
        $this->_sanitizeTargetUnitId($localUnit);
        $this->_sanitizeBridgeUnitId($localUnit);


        // Local ids
        if (!$localPrimaryIndex = $localSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$localUnit->getUnitId().' does not have a primary index'
            );
        }

        return $this;
    }

    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $localSchema)
    {
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $this->_validateDefaultValue($localUnit);

        $bridgeUnit = $this->_validateBridgeUnit($localUnit);

        return $this;
    }



    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setRelationStorageArray($data);
        $this->_setBridgeRelationStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getRelationStorageArray(),
            $this->_getBridgeRelationStorageArray()
        );
    }
}
