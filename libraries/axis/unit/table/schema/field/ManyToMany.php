<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\schema\field;

use df;
use df\core;
use df\axis;

class ManyToMany extends Many implements IManyToManyField {
    
    protected $_isDominant = false;
    protected $_localPrimaryFields = array('id');
    protected $_targetPrimaryFields = array('id');
     
    protected $_bridgeUnitId;
    protected $_targetField;
    protected $_targetUnitId;
    
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
    public function sanitizeValue($value, $forRecord) {
        if($forRecord) {
            return new axis\unit\table\record\BridgedManyRelationValueContainer(
                $this->_bridgeUnitId,
                $this->_targetUnitId,
                $this->_localPrimaryFields,
                $this->_targetPrimaryFields,
                $this->_isDominant
            );
        } else {
            return $value;
        }
    }
    
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $model = $unit->getModel();
        
        // Target unit id
        if(false === strpos($this->_targetUnitId, axis\Unit::ID_SEPARATOR)) {
            $this->_targetUnitId = $model->getModelName().axis\Unit::ID_SEPARATOR.$this->_targetUnitId;
        }
        
        // Bridge unit id
        if($this->_isDominant && empty($this->_bridgeUnitId)) {
            $this->_bridgeUnitId = $model->getModelName().axis\Unit::ID_SEPARATOR.'table.ManyBridge('.$unit->getUnitName().'.'.$this->_name.')';
        }
        
        if(!empty($this->_bridgeUnitId) && false === strpos($this->_bridgeUnitId, axis\Unit::ID_SEPARATOR)) {
            $this->_bridgeUnitId = $model->getModelName().axis\Unit::ID_SEPARATOR.$this->_bridgeUnitId;
        }
        
        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        // Local ids
        $localSchema = $localUnit->getTransientUnitSchema();
        
        if(!$localPrimaryIndex = $localSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$localUnit->getUnitId().' does not have a primary index'
            );
        }
        
        $this->_localPrimaryFields = array_keys($localPrimaryIndex->getFields());
        
        
        // Target ids
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $localUnit->getApplication());
        
        if(!$targetUnit instanceof axis\unit\table\Base) {
            throw new axis\schema\RuntimeException(
                'Relation target unit '.$targetUnit->getUnitId().' is not a table'
            );
        }
        
        $targetSchema = $targetUnit->getTransientUnitSchema();
        
        if(!$targetPrimaryIndex = $targetSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$targetUnit->getUnitId().' does not have a primary index'
            );
        }
        
        $this->_targetPrimaryFields = array_keys($targetPrimaryIndex->getFields());
        
        
        
        // Target field
        if(!$targetField = $targetSchema->getField($this->_targetField)) {
            throw new axis\schema\RuntimeException(
                'Target field '.$this->_targetField.' could not be found in '.$localUnit->getUnitId()
            );
        }
        
        if(!$targetField instanceof IManyToManyField) {
            throw new axis\schema\RuntimeException(
                'Target field '.$this->_targetField.' is not a ManyToMany field'
            );
        }
        
        // Dominance
        if($this->_isDominant == $targetField->isDominant()) {
            throw new axis\schema\RuntimeException(
                'Paired ManyToManyFields must nominate one side to be dominant'
            );
        }
        
        if($this->_isDominant) {
            $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId);
            $bridgeSchema = $bridgeUnit->getTransientUnitSchema();
            
            if($bridgeUnit->getModel()->getModelName() != $localUnit->getModel()->getModelName()) {
                throw new axis\schema\RuntimeException(
                    'Bridge units must be local to the dominant participant - '.
                    $this->_bridgeUnitId.' should be on model '.$localUnit->getModel()->getModelName()
                );
            }
        } else {
            $this->_bridgeUnitId = $targetField->getBridgeUnitId();
            $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId);
        }
        
        // TODO: validate default value
        
        return $this;
    }


// Ext. serialize
    protected function _importStorageArray(array $data) {
        parent::_importStorageArray($data);
        
        $this->_isDominant = $data['dom'];
        $this->_targetField = $data['tfl'];
    }
    
    public function toStorageArray() {
        return array_merge(
            parent::toStorageArray(),
            [
                'dom' => $this->_isDominant,
                'tfl' => $this->_targetField
            ]
        );
    }
}
