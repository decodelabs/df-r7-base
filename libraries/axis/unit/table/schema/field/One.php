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
class One extends axis\schema\field\Base implements axis\schema\IOneField {
    
    use axis\schema\TRelationField;

    protected $_targetPrimaryFields = array('id');
    
    protected function _init($targetTableUnit) {
        $this->setTargetUnitId($targetTableUnit);
    }

    
// Values
    public function inflateValueFromRow($key, array $row, opal\query\record\IRecord $forRecord=null) {
        $hasKey = array_key_exists($key, $row);

        if($hasKey) {
            $value = $row[$key];
        } else {
            $value = array();
            
            foreach($this->_targetPrimaryFields as $field) {
                $fieldKey = $key.'_'.$field;
                
                if(isset($row[$fieldKey])) {
                    $value[$field] = $row[$fieldKey];
                } else {
                    $value[$field] = null;
                }
            }
        }


        if(!$forRecord) {
            // Only need a simple value
            if($hasKey) {
                return $row[$key];
            } else {
                return new opal\query\record\PrimaryManifest($this->_targetPrimaryFields, $value);
            }
        }


        // Need to build a value container
        $targetField = null;

        if($this instanceof axis\schema\IInverseRelationField) {
            $targetField = $this->_targetField;

            if($value instanceof opal\query\IRecord) {
                $inverseValue = $value->getRaw($targetField);
                $inverseValue->populateInverse($forRecord);
            }
        }


        return new axis\unit\table\record\OneRelationValueContainer(
            $value, $this->_targetUnitId, $this->_targetPrimaryFields, $targetField
        );
    }
    
    public function deflateValue($value) {
        if(!$value instanceof opal\query\record\IPrimaryManifest) {
            $value = new opal\query\record\PrimaryManifest($this->_targetPrimaryFields, $value);
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
            $value, $this->_targetUnitId, $this->_targetPrimaryFields
        );
    }
    
    public function generateInsertValue(array $row) {
        return null;
    }


// Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        return $field->getSource()->getAdapter()->mapVirtualClause(
            $parent, $field, $operator, $value, $isOr, $this->_targetPrimaryFields, $this->_name
        );
    }


// Populate
    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate) {
        $output = opal\query\FetchAttach::fromPopulate($populate);

        $parentSourceAlias = $populate->getParentSourceAlias();
        $targetSourceAlias = $populate->getSourceAlias();
        
        $output->on($targetSourceAlias.'.@primary', '=', $parentSourceAlias.'.'.$this->_name);
        $output->asOne($this->_name);

        return $output;
    }
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        $this->_sanitizeTargetUnitId($localUnit);

        if(!$schema->hasIndex($this->_name)) {
            $schema->addIndex($this->_name);
        }
        
        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        // Target
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        

        // Primary fields
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

    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $unit->getApplication());
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $output = array();

        foreach($this->_targetPrimaryFields as $fieldName) {
            $field = $targetSchema->getField($fieldName);

            $dupField = $field->duplicateForRelation($targetUnit, $targetSchema);
            $dupField->_setName($this->_name.'_'.$dupField->getName());

            $output[] = $dupField;
        }

        return $output;
    }
    
    
// Primitive
    public function getPrimitiveFieldNames() {
        $output = array();
        
        foreach($this->_targetPrimaryFields as $field) {
            $output[] = $this->_name.'_'.$field;
        }
        
        return $output;
    }

    
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        
        $this->_targetPrimaryFields = (array)$data['tpf'];
        $this->_targetUnitId = $data['tui'];
    }
    
    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            [
                'tpf' => $this->_targetPrimaryFields,
                'tui' => $this->_targetUnitId
            ]
        );
    }
    
    
// Dump
    public function getDumpProperties() {
        return parent::getDumpProperties().' '.$this->_targetUnitId.' : '.implode(', ', $this->_targetPrimaryFields);
    }
}
