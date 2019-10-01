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

class KeyGroup extends Base implements
    axis\schema\IRelationField,
    opal\schema\IOneRelationField,
    opal\schema\IMultiPrimitiveField,
    opal\schema\ITargetPrimaryFieldAwareRelationField
{
    use axis\schema\TRelationField;
    use axis\schema\TTargetPrimaryFieldAwareRelationField;

    protected function _init($targetTableUnit)
    {
        $this->setTargetUnitId($targetTableUnit);
    }


    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null)
    {
        $value = $this->getTargetRelationManifest()->extractFromRow($key, $row);

        if (!$forRecord) {
            if (is_array($value)) {
                return $this->getTargetRelationManifest()->toPrimaryKeySet($value);
            } else {
                return $value;
            }
        }

        // Need to build a value container
        return new axis\unit\table\record\OneRelationValueContainer(
            $this, $forRecord, $value
        );
    }

    public function deflateValue($value)
    {
        if (!$value instanceof opal\record\IPrimaryKeySet) {
            $value = $this->getTargetRelationManifest()->toPrimaryKeySet($value);
        }

        $output = [];
        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId);
        $schema = $targetUnit->getUnitSchema();

        foreach ($value->toArray() as $key => $value) {
            if ($field = $schema->getField($key)) {
                $value = $field->deflateValue($value);
            }

            $output[$this->_name.'_'.$key] = $value;
        }

        return $output;
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord=null)
    {
        if (!$forRecord) {
            return $value;
        }

        return new axis\unit\table\record\OneRelationValueContainer(
            $this, $forRecord, $value
        );
    }


    public function generateInsertValue(array $row)
    {
        return null;
    }


    // Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false)
    {
        return opal\query\clause\Clause::mapVirtualClause(
            $parent, $field, $operator, $value, $isOr
        );
    }


    // Populate
    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate)
    {
        $output = opal\query\Initiator::beginAttachFromPopulate($populate);

        $parentSourceAlias = $populate->getParentSourceAlias();
        $targetSourceAlias = $populate->getSourceAlias();

        $output->on($targetSourceAlias.'.@primary', '=', $parentSourceAlias.'.'.$this->_name);
        $output->asOne($this->_name);

        return $output;
    }


    // Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema)
    {
        $this->_sanitizeTargetUnitId($localUnit);
    }

    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema)
    {
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
    }

    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        Glitch::incomplete('Seriously.. why are you using this type of field in a 3rd party relation!?!?!?');
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
        $this->_targetUnitId = $data['tui'];
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            [
                'tui' => $this->_targetUnitId
            ]
        );
    }
}
