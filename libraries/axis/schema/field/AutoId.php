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

class AutoId extends Base implements
    opal\schema\IByteSizeRestrictedField,
    axis\schema\IAutoGeneratorField,
    axis\schema\IAutoPrimaryField {
        
    use opal\schema\TField_ByteSizeRestricted;
    
    protected function _init($size=null) {
        $this->setByteSize($size);
    }
    
    
// Auto inc
    public function shouldAutoGenerate($flag=null) {
        if($flag !== null) {
            if(!$flag) {
                throw new opal\schema\LogicException(
                    'AutoId field must auto increment'
                );
            }
            
            return $this;
        }
        
        return true;
    }
    
    
// Primitive
    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = new Integer($schema, 'Integer', $this->_name, array($this->_byteSize));
        $output->isUnsigned(true);
        
        return $output;
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = new opal\schema\Primitive_Integer($this, $this->_byteSize);
        $output->isUnsigned(true);
        $output->shouldAutoIncrement(true);
        
        return $output;
    }
}
