<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\field;

use df\axis;

class OneParent extends One implements axis\schema\IOneParentField
{
    use axis\schema\TInverseRelationField;

    protected function _init($targetUnit, $targetField = null)
    {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
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
}
