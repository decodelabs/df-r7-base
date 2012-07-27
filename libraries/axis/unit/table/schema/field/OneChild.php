<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;

class OneChild extends axis\schema\field\Base implements axis\schema\IOneChildField {
    
    use axis\schema\TRelationField;
    use axis\schema\TInverseRelationField;

    protected function _init($targetUnit, $targetField=null) {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, $forRecord) {
        return $this->sanitizeValue(null, $forRecord);
    }
    
    public function deflateValue($value) {
        return null;
    }
    
    public function sanitizeValue($value, $forRecord) {
        if($forRecord) {
            $output = new axis\unit\table\record\OneChildRelationValueContainer(
                $this->_targetUnitId, $this->_targetField
            );

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


// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Null($this);
    }
    
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        
        $this->_targetUnitId = $data['tui'];
        $this->_targetField = $data['tfl'];
    }
    
    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            [
                'tui' => $this->_targetUnitId,
                'tfl' => $this->_targetField
            ]
        );
    }
}
