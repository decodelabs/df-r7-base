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
 * This type does not care about inverse at all.
 * Just return primary primitive
 */
class One extends axis\schema\field\Base implements IOneField {
    
    protected $_primaryFields = array('id');
    protected $_targetUnitId;
    
    protected function _init($targetTableUnit) {
        $this->setTargetUnitId($targetTableUnit);
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
                $values, $this->_targetUnitId, $this->_primaryFields
            );
        } else {
            return new opal\query\record\PrimaryManifest($this->_primaryFields, $values);
        }
    }
    
    public function deflateValue($value) {
        if(!$value instanceof opal\query\record\IPrimaryManifest) {
            $value = new opal\query\record\PrimaryManifest($this->_primaryFields, $value);
        }
        
        $output = array();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId);
        $schema = $targetUnit->getUnitSchema();
        
        foreach($value->toArray() as $key => $value) {
            if($field = $schema->getField($key)) {
                $value = $field->deflateValue($value);
            }
            
            $output[$this->_name.'_'.$key] = $value;
        }
        
        return $output;
    }
    
    public function sanitizeValue($value, $forRecord) {
        if(!$forRecord) {
            return $value;
        }
        
        return new axis\unit\table\record\OneRelationValueContainer(
            $value, $this->_targetUnitId, $this->_primaryFields
        );
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
    
    
// Primitive
    public function getPrimitiveFieldNames() {
        $output = array();
        
        foreach($this->_primaryFields as $field) {
            $output[] = $this->_name.'_'.$field;
        }
        
        return $output;
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $application = $unit->getApplication();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $primaryIndex = $targetSchema->getPrimaryIndex();
        
        $primitives = array();
        
        foreach($primaryIndex->getFields() as $name => $field) {
            $primitive = $field->toPrimitive($targetUnit, $targetSchema)
                ->_setName($this->_name.'_'.$name)
                ->isNullable(true);
                
            if($this->_defaultValue !== null) {
                $primitive->setDefaultValue($this->_defaultValue[$name]);
            }
            
            if($primitive instanceof opal\schema\IAutoIncrementableField) {
                $primitive->shouldAutoIncrement(false);
            }
            
            $primitives[$name] = $primitive;
        }
        
        
        if(count($primitives) == 1) {
            return array_shift($primitives);
        }
        
        return new opal\schema\Primitive_MultiField($this, $primitives);
    }
    
    
// Dump
    public function getDumpProperties() {
        return parent::getDumpProperties().' '.$this->_targetUnitId.' : '.implode(', ', $this->_primaryFields);
    }
}
