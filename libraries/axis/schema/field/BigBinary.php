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

class BigBinary extends Base implements opal\schema\ILargeByteSizeRestrictedField {
    
    use opal\schema\TField_LargeByteSizeRestricted;
    
    protected function _init($size=null) {
        $this->setExponentSize($size);
    }

    public function compareValues($value1, $value2) {
        return (string)$value1 === (string)$value2;
    }
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Blob($this, $this->_exponentSize);
    }
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setLargeByteSizeRestrictedStorageArray($data);
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getLargeByteSizeRestrictedStorageArray()
        );
    }
}
