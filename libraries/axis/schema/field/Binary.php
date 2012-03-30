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
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        if($this->_isConstantLength) {
            return new opal\schema\Primitive_Binary($this, $this->_length);
        } else {
            return new opal\schema\Primitive_Varbinary($this, $this->_length);
        }
    }
}
