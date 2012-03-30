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

class Integer extends Base implements 
    opal\schema\IByteSizeRestrictedField, 
    opal\schema\INumericField, 
    opal\schema\IAutoIncrementableField {
    
    use opal\schema\TField_ByteSizeRestricted;
    use opal\schema\TField_Numeric;
    use opal\schema\TField_AutoIncrementable;
    
    protected function _init($size=null) {
        $this->setByteSize($size);
    }
    

// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = new opal\schema\Primitive_Integer($this, $this->_byteSize);
        
        if($this->_isUnsigned) {
            $output->isUnsigned(true);
        }
        
        if($this->_zerofill) {
            $output->shouldZerofill(true);
        }
        
        if($this->_autoIncrement) {
            $output->shouldAutoIncrement(true);
        }
        
        return $output;
    }
}
