<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\schema\field;

use df;
use df\core;
use df\axis;

class OneParent extends axis\schema\field\Base implements IOneParentField {
    
    protected $_targetUnitId;
    protected $_targetField;
    
    protected function _init($targetUnit, $targetField=null) {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }
    
    
// Target unit id
    public function setTargetUnitId($targetUnit) {
        if($targetUnit instanceof axis\IUnit) {
            $targetUnit = $targetUnit->getUnitId();
        }
        
        $targetUnit = (string)$targetUnit;
        
        if($targetUnit != $this->_targetUnitId) {
            $this->_hasChanged = true;
        }
        
        $this->_targetUnitId = $targetUnit;
        return $this;
    }
    
    public function getTargetUnitId() {
        return $this->_targetUnitId;
    }
    
    
// Target field
    public function setTargetField($field) {
        if($field != $this->_targetField) {
            $this->_hasChanged = true;
        }
        
        $this->_targetField = $field;
        return $this;
    }
    
    public function getTargetField() {
        return $this->_targetField;
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
            return new axis\unit\table\record\OneParentRelationValueContainer(
                $this->_targetUnitId, $this->_targetField
            );
        } else {
            return null;
        }
    }
    
    public function generateInsertValue(array $row) {
        return null;
    }
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $model = $unit->getModel();
        
        // Target unit id
        if(false === strpos($this->_targetUnitId, axis\Unit::ID_SEPARATOR)) {
            $this->_targetUnitId = $model->getModelName().axis\Unit::ID_SEPARATOR.$this->_targetUnitId;
        }
        
        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        // Target
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $unit->getApplication());
        
        if(!$targetUnit instanceof axis\unit\table\Base) {
            throw new axis\schema\RuntimeException(
                'Relation target unit '.$targetUnit->getUnitId().' is not a table'
            );
        }
        
        $targetSchema = $targetUnit->getTransientUnitSchema();
        
        if(!$primaryIndex = $targetSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$targetUnit->getUnitId().' does not have a primary index'
            );
        }
        
        
        // Target field
        if(!$targetField = $targetSchema->getField($this->_targetField)) {
            throw new axis\schema\RuntimeException(
                'Target field '.$this->_targetField.' could not be found in '.$unit->getUnitId()
            );
        }
        
        if(!$targetField instanceof IOneChildField) {
            throw new axis\schema\RuntimeException(
                'Target field '.$this->_targetField.' is not a OneChild field'
            );
        }
    }


// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Null($this);
    }
}
