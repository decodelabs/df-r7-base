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
    
class KeyGroup extends Base implements axis\schema\IMultiPrimitiveField, axis\schema\IQueryClauseRewriterField {

	use axis\schema\TRelationField;

	protected $_targetPrimaryFields = array('id');

    protected function _init($targetTableUnit) {
        $this->setTargetUnitId($targetTableUnit);
    }


// Values
    public function inflateValueFromRow($key, array $row, $forRecord) {
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
                $values, $this->_targetUnitId, $this->_targetPrimaryFields
            );
        } else {
            return new opal\query\record\PrimaryManifest($this->_targetPrimaryFields, $values);
        }
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
        $fieldCount = count($this->_targetPrimaryFields);
        $output = null;


        if($fieldCount > 1) {
        	$output = new opal\query\clause\WhereList($parent, $isOr);
        	$clauseFactory = $output;
        } else {
        	$clauseFactory = $parent;
        }

        if($value instanceof opal\query\IVirtualField) {
        	$valueFields = array();
        	
        	foreach($value->getTargetFields() as $field) {
        		$valueFields[$field->getName()] = $field;
        	}
        } else if($value instanceof opal\query\record\IPrimaryManifest) {
       		$value = $value->getIntrinsicFieldMap($this->_name);
        } else if(is_scalar($value) && $fieldCount > 1) {
        	throw new axis\schema\RuntimeException(
				'KeyGroup fields do not match on '.
				$parent->getSource()->getAdapter()->getName().':'.$this->_name
			);
        }

        foreach($this->_targetPrimaryFields as $fieldName) {
        	$subValue = null;
        	$keyName = $this->_name.'_'.$fieldName;

        	if($value instanceof opal\query\IVirtualField) {
        		if(!isset($valueFields[$keyName])) {
        			throw new axis\schema\RuntimeException(
        				'KeyGroup join fields do not match between '.
        				$parent->getSource()->getAdapter()->getName().':'.$this->_name.' and '.
        				$value->getSource()->getAdapter()->getName().':'.$value->getName().
        				' for keyName '.$keyName
    				);
        		}
        		
        		$subValue = $valueFields[$keyName];
        	} else if(is_array($value)) {
        		if(!isset($value[$keyName])) {
        			throw new axis\schema\RuntimeException(
        				'KeyGroup fields do not match on '.
        				$parent->getSource()->getAdapter()->getUnitId().':'.$this->_name.
        				' for keyName '.$keyName
    				);
        		}
        		
        		$subValue = $value[$keyName];
        	} else {
        		$subValue = $value;
        	}

        	$newField = new opal\query\field\Intrinsic($field->getSource(), $keyName);
        	$clause = opal\query\clause\Clause::factory($clauseFactory, $newField, $operator, $subValue);

        	if($output) {
        		$output->_addClause($clause);
        	} else {
        		return $clause;
        	}
        }

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