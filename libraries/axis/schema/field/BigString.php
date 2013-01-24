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

class BigString extends Base implements 
    opal\schema\ILargeByteSizeRestrictedField, 
    opal\schema\ICharacterSetAwareField {
    
    use opal\schema\TField_LargeByteSizeRestricted;
    use opal\schema\TField_CharacterSetAware;
    
    protected function _init($size=null) {
        $this->setExponentSize($size);
    }

    public function compareValues($value1, $value2) {
        return (string)$value1 === (string)$value2;
    }

    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = new opal\schema\Primitive_Text($this, $this->_exponentSize);
        
        if($this->_characterSet !== null) {
            $output->setCharacterSet($this->_characterSet);
        }
        
        return $output;
    }
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setLargeByteSizeRestrictedStorageArray($data);
        $this->_setCharacterSetStorageArray($data);
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getLargeByteSizeRestrictedStorageArray(),
            $this->_getCharacterSetStorageArray()
        );
    }
}
