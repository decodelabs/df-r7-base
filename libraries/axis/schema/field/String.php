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

class String extends Base implements 
    axis\schema\ILengthRestrictedField, 
    opal\schema\ICharacterSetAwareField {
    
    use axis\schema\TLengthRestrictedField;
    use opal\schema\TField_CharacterSetAware;
    
    protected function _init($length=null) {
        $this->setLength($length);
    }
    
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        if($this->_isConstantLength) {
            $output = new opal\schema\Primitive_Char($this, $this->_length);
        } else {
            $output = new opal\schema\Primitive_Varchar($this, $this->_length);
        }
        
        if($this->_characterSet !== null) {
            $output->setCharacterSet($this->_characterSet);
        }
        
        return $output;
    }
    
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setLengthRestrictedStorageArray($data);
        $this->_setCharacterSetStorageArray($data);
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getLengthRestrictedStorageArray(),
            $this->_getCharacterSetStorageArray()
        );
    }
}
