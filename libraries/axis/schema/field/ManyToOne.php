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

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

/*
 * This type requires an inverse field and must lookup and match target table.
 * Key resides here - inverse primary primitive
 */
class ManyToOne extends One implements axis\schema\IManyToOneField, opal\schema\IQueryClauseRewriterField
{
    use axis\schema\TInverseRelationField;

    protected function _init($targetUnit, $targetField=null)
    {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }


    // Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false)
    {
        return opal\query\clause\Clause::mapVirtualClause(
            $parent, $field, $operator, $value, $isOr
        );
    }


    // Validate
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema)
    {
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
        parent::_importStorageArray($data);
        $this->_setInverseRelationStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            parent::toStorageArray(),
            $this->_getInverseRelationStorageArray()
        );
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        parent::glitchInspect($entity, $inspector);
        $def = $entity->getDefinition();

        if ($this->_targetField) {
            $def = substr((string)$def, 0, -1).' -> '.$this->_targetField.')';
        }

        $entity->setDefinition($def);
    }
}
