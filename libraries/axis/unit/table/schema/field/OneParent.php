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

class OneParent extends One implements axis\schema\IOneParentField {
    
    use axis\schema\TInverseRelationField;

    protected function _init($targetUnit, $targetField=null) {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, opal\query\record\IRecord $forRecord=null) {
        $values = array();
        
        foreach($this->_targetPrimaryFields as $field) {
            $fieldKey = $key.'_'.$field;
            
            if(isset($row[$fieldKey])) {
                $values[$field] = $row[$fieldKey];
            } else {
                $values[$field] = null;
            }
        }
        
        if($forRecord) {
            return new axis\unit\table\record\OneRelationValueContainer(
                $values, $this->_targetUnitId, $this->_targetPrimaryFields, $this->_targetField
            );
        } else {
            return new opal\query\record\PrimaryManifest($this->_targetPrimaryFields, $values);
        }
    }
    
    
// Validate
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        // Target
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $targetField = $this->_validateInverseRelationField($targetUnit, $targetSchema);
        
        $this->_targetPrimaryFields = array_keys($targetPrimaryIndex->getFields());
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
