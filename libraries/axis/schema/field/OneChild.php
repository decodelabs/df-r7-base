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

class OneChild extends Base implements axis\schema\IOneChildField {
    
    use axis\schema\TRelationField;
    use axis\schema\TInverseRelationField;

    protected function _init($targetUnit, $targetField=null) {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        return $this->sanitizeValue(null, $forRecord);
    }
    
    public function deflateValue($value) {
        return null;
    }
    
    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if($forRecord) {
            $output = new axis\unit\table\record\OneChildRelationValueContainer($this);

            if($value !== null) {
                $output->setValue($value);
            }

            return $output;
        } else {
            return null;
        }
    }
    
    public function generateInsertValue(array $row) {
        return null;
    }


// Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        core\stub($parent, $field, $operator, $value, $isOr);
    }
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        $this->_sanitizeTargetUnitId($localUnit);

        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $targetField = $this->_validateInverseRelationField($targetUnit, $targetSchema);
    }

    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setRelationStorageArray($data);
        $this->_setInverseRelationStorageArray($data);
    }
    
    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getRelationStorageArray(),
            $this->_getInverseRelationStorageArray()
        );
    }
}