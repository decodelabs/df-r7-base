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

class Timestamp extends Base implements opal\schema\IAutoTimestampField {
    
    use opal\schema\TField_AutoTimestamp;
    
    /*
    public function setDefaultValue($value) {
        if($value !== null) {
            $this->_shouldTimestampAsDefault = false;
        }
        
        return parent::setDefaultValue($value);
    }
    */
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = new opal\schema\Primitive_Timestamp($this);
        $output->shouldTimestampOnUpdate($this->_shouldTimestampOnUpdate);
        $output->shouldTimestampAsDefault($this->_shouldTimestampAsDefault);
        
        return $output;
    } 
}
