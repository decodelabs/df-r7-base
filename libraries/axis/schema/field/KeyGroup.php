<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;
    
class KeyGroup extends Base implements axis\schema\IRelationField, axis\schema\IMultiPrimitiveField {

    use axis\schema\TRelationField;

    protected $_targetPrimaryFields = array('id');

    protected function _init($targetTableUnit) {
        $this->setTargetUnitId($targetTableUnit);
    }


// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
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
            $output = new axis\unit\table\record\OneRelationValueContainer(
                $forRecord, $values, $this->_targetUnitId, $this->_targetPrimaryFields
            );

            if(isset($row[$key])) {
                $output->populateInverse($row[$key]);
            }

            return $output;
        } else {
            if(isset($row[$key])) {
                return $row[$key];
            }
            
            return new opal\record\PrimaryManifest($this->_targetPrimaryFields, $values);
        }
    }

    public function deflateValue($value) {
        if(!$value instanceof opal\record\IPrimaryManifest) {
            $value = new opal\record\PrimaryManifest($this->_targetPrimaryFields, $value);
        }
        
        $output = array();
        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId);
        $schema = $targetUnit->getUnitSchema();
        
        foreach($value->toArray() as $key => $value) {
            if($field = $schema->getField($key)) {
                $value = $field->deflateValue($value);
            }
            
            $output[$this->_name.'_'.$key] = $value;
        }

        return $output;
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if(!$forRecord) {
            return $value;
        }
        
        return new axis\unit\table\record\OneRelationValueContainer(
            $forRecord, $value, $this->_targetUnitId, $this->_targetPrimaryFields
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
    }

    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
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
    }

    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        core\stub('Seriously.. why are you using this type of field in a 3rd party relation!?!?!?');
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
}