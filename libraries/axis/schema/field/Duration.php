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

class Duration extends Base implements opal\schema\ISignedField {
    
    use opal\schema\TField_Signed;

    protected $_referenceDateField;


// Reference date
    public function setReferenceDateField($fieldName) {
        $this->_referenceDateField = $fieldName;
        return $this;
    }

    public function getReferenceDateField() {
        return $this->_referenceDateField;
    }

// Values
    public function inflateValueFromRow($key, array $row, $forRecord) {
        if(isset($row[$key])) { 
            $refDate = null;

            if($this->_referenceDateField && isset($row[$this->_referenceDateField])) {
                $refDate = core\time\Date::factory($row[$this->_referenceDateField]);
            }

            return new core\time\Duration($row[$key], $refDate);
        } else {
            return null;
        } 
    }
    
    public function deflateValue($value) {
        $value = $this->sanitizeValue($value, true);
        
        if(empty($value)) {
            return null;
        }
        
        return $value->getSeconds();
    }
    
    public function sanitizeValue($value, $forRecord) {
        if(empty($value)) {
            if($this->isNullable()) {
                return null;
            } else if(!empty($this->_defaultValue)) {
                $value = $this->_defaultValue;
            } else {
                $value = 0;
            }
        }
        
        return core\time\Duration::factory($value);
    }



// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        if($this->_referenceDateField !== null) {
            if(!$field = $schema->getField($this->_referenceDateField)) {
                throw new axis\schema\RuntimeException(
                    'Reference date field '.$this->_referenceDateField.' could not be found in schema '.$schema->getName()
                );
            }

            if(!$field instanceof axis\schema\IDateField) {
                throw new axis\schema\RuntimeException(
                    'Reference date field '.$this->_referenceDateField.' is not a date type field'
                );
            }
        }
    }
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = new opal\schema\Primitive_Integer($this, 8);

        if($this->_isUnsigned) {
            $output->isUnsigned(true);
        }

        return $output;
    }
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setSignedStorageArray($data);

        $this->_referenceDateField = $data['rdf'];
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getSignedStorageArray(),
            ['rdf' => $this->_referenceDateField]
        );
    }
}
