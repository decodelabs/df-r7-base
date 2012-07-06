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

class OneParent extends One implements IOneParentField {
    
    protected $_primaryFields = array('id');
    protected $_targetUnitId;
    protected $_targetField;
    
    protected function _init($targetUnit, $targetField=null) {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
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
        $values = array();
        
        foreach($this->_primaryFields as $field) {
            $fieldKey = $key.'_'.$field;
            
            if(isset($row[$fieldKey])) {
                $values[$field] = $row[$fieldKey];
            } else {
                $values[$field] = null;
            }
        }
        
        if($forRecord) {
            return new axis\unit\table\record\OneRelationValueContainer(
                $values, $this->_targetUnitId, $this->_primaryFields, $this->_targetField
            );
        } else {
            return new opal\query\record\PrimaryManifest($this->_primaryFields, $values);
        }
    }
    
    
// Validate
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
        
        $this->_primaryFields = array_keys($primaryIndex->getFields());
        
        
        // Default value
        if($this->_defaultValue !== null) {
            if(!is_array($this->_defaultValue)) {
                if(count($this->_primaryFields) > 1) {
                    throw new axis\schema\RuntimeException(
                        'Default value for a multi key relation must be a keyed array'
                    );
                }
                
                $this->_defaultValue = array($this->_primaryFields[0] => $this->_defaultValue);
            }
            
            foreach($this->_primaryFields as $field) {
                if(!array_key_exists($field, $this->_defaultValue)) {
                    throw new axis\schema\RuntimeException(
                        'Default value for a multi key relation must contain values for all target primary keys'
                    );
                }
            }
        }
        
        return $this;
    }

// Ext. serialize
    protected function _importStorageArray(array $data) {
        parent::_importStorageArray($data);
        
        $this->_targetField = $data['tfl'];
    }
    
    public function toStorageArray() {
        return array_merge(
            parent::toStorageArray(),
            [
                'tfl' => $this->_targetField
            ]
        );
    }
}
