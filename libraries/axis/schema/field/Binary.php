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

class Binary extends Base implements 
    axis\schema\ILengthRestrictedField {
        
    use axis\schema\TLengthRestrictedField;
    
    protected function _init($length=null) {
        $this->setLength($length);
    }

    public function compareValues($value1, $value2) {
        return (string)$value1 === (string)$value2;
    }
    
    public function getSearchFieldType() {
        return 'string';
    }
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        if($this->_isConstantLength) {
            return new opal\schema\Primitive_Binary($this, $this->_length);
        } else {
            return new opal\schema\Primitive_Varbinary($this, $this->_length);
        }
    }
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setLengthRestrictedStorageArray($data);
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getLengthRestrictedStorageArray()
        );
    }
}
