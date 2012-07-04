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
 * Key resides on Many side, null primitive
 */
class OneToMany extends axis\schema\field\Base implements IOneToManyField {
    
    protected $_targetUnitId;
    protected $_targetField;
    protected $_targetPrimaryFields = array('id');
    
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
            $output = new axis\unit\table\record\InlineManyRelationValueContainer(
                $this->_targetUnitId,
                $this->_targetField,
                $this->_targetPrimaryFields
            );

            if(is_array($value)) {
                foreach($value as $entry) {
                    $output->add($entry);
                }
            }

            return $output;
        } else {
            return $value;
        }
    }
    
    public function generateInsertValue(array $row) {
        return null;
    }
    
    
// Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        core\stub($parent, $field, $operator, $value);
    }
    

// Validate
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $model = $unit->getModel();
        
        // Target unit id
        if(false === strpos($this->_targetUnitId, axis\Unit::ID_SEPARATOR)) {
            $this->_targetUnitId = $model->getModelName().axis\Unit::ID_SEPARATOR.$this->_targetUnitId;
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
        
        
        // Target field
        $targetSchema = $targetUnit->getTransientUnitSchema();
        
        if(!$targetField = $targetSchema->getField($this->_targetField)) {
            throw new axis\schema\RuntimeException(
                'Target field '.$this->_targetField.' could not be found in '.$localUnit->getUnitId()
            );
        }
        
        if(!$targetField instanceof IManyToOneField) {
            throw new axis\schema\RuntimeException(
                'Target field '.$this->_targetField.' is not a ManyToOne field'
            );
        }
        
        // Target primary
        if(!$targetPrimaryIndex = $targetSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$targetUnit->getUnitId().' does not have a primary index'
            );
        }
        
        $this->_targetPrimaryFields = array_keys($targetPrimaryIndex->getFields());
        
        // TODO: validate default value
        
        return $this;
    }
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Null($this);
    }
    
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        
        $this->_targetPrimaryFields = (array)$data['tpf'];
        $this->_targetUnitId = $data['tui'];
        $this->_targetField = $data['tfl'];
    }
    
    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            [
                'tpf' => $this->_targetPrimaryFields,
                'tui' => $this->_targetUnitId,
                'tfl' => $this->_targetField
            ]
        );
    }
}
