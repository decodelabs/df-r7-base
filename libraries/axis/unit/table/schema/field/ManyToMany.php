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

class ManyToMany extends Many implements axis\schema\IManyToManyField {
    
    use axis\schema\TInverseRelationField;

    protected $_isDominant = false;
     
    protected function _init($targetUnit, $targetField=null) {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }
    
    
    public function isDominant($flag=null) {
        if($flag !== null) {
            $flag = (bool)$flag;
            
            if($this->_isDominant != $flag) {
                $this->_hasChanged = true;
            }
            
            $this->_isDominant = $flag;
            return $this;
        }
        
        return $this->_isDominant;
    }
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $localSchema) {
        $this->_sanitizeTargetUnitId($localUnit);
        $this->_sanitizeBridgeUnitId($localUnit);


        // Local ids
        if(!$localPrimaryIndex = $localSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$localUnit->getUnitId().' does not have a primary index'
            );
        }
        
        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $localSchema) {
        // Target
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $targetField = $this->_validateInverseRelationField($targetUnit, $targetSchema);
        
        
        // Dominance
        if($this->_isDominant == $targetField->isDominant()) {
            throw new axis\schema\RuntimeException(
                'Paired ManyToManyFields must nominate one side to be dominant'
            );
        }
        
        if($this->_isDominant) {
            $bridgeUnit = $this->_validateBridgeUnit($localUnit);
        } else {
            $this->_bridgeUnitId = $targetField->getBridgeUnitId();
        }
        
        $this->_validateDefaultValue($localUnit);
        
        return $this;
    }


// Ext. serialize
    protected function _importStorageArray(array $data) {
        parent::_importStorageArray($data);
        
        $this->_setInverseRelationStorageArray($data);
        $this->_isDominant = $data['dom'];
    }
    
    public function toStorageArray() {
        return array_merge(
            parent::toStorageArray(),
            $this->_getInverseRelationStorageArray(),
            [
                'dom' => $this->_isDominant
            ]
        );
    }
}
