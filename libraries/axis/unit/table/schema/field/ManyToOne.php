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


/*
 * This type requires an inverse field and must lookup and match target table.
 * Key resides here - inverse primary primitive
 */
class ManyToOne extends One implements axis\schema\IManyToOneField, axis\schema\IQueryClauseRewriterField {
    
    use axis\schema\TInverseRelationField;

    protected function _init($targetUnit, $targetField=null) {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }
    

// Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        return $field->getSource()->getAdapter()->mapVirtualClause(
            $parent, $field, $operator, $value, $isOr, $this->_targetPrimaryFields, $this->_name
        );
    }
    
    
// Validate
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        // Target
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $targetField = $this->_validateInverseRelationField($targetUnit, $targetSchema);
        
        
        $this->_targetPrimaryFields = array();
        
        foreach($targetPrimaryIndex->getFields() as $name => $field) {
            if($field instanceof axis\schema\IMultiPrimitiveField) {
                foreach($field->getPrimitiveFieldNames() as $name) {
                    $this->_targetPrimaryFields[] = $name;
                }
            } else {
                $this->_targetPrimaryFields[] = $name;
            }
        }
        
        $this->_validateDefaultValue($localUnit, $this->_targetPrimaryFields);
        
        return $this;
    }

// Ext. serialize
    protected function _importStorageArray(array $data) {
        parent::_importStorageArray($data);
        $this->_setInverseRelationStorageArray($data);
    }
    
    public function toStorageArray() {
        return array_merge(
            parent::toStorageArray(),
            $this->_getInverseRelationStorageArray()
        );
    }
}
