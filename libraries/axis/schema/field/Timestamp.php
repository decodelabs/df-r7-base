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

    public function deflateValue($value) {
        if(empty($value)) {
            $value = null;
        }

        return $value;
    }

    public function compareValues($value1, $value2) {
        return (string)$value1 === (string)$value2;
    }
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = new opal\schema\Primitive_Timestamp($this);
        $output->shouldTimestampOnUpdate($this->_shouldTimestampOnUpdate);
        $output->shouldTimestampAsDefault($this->_shouldTimestampAsDefault);
        
        return $output;
    } 
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setAutoTimestampStorageArray($data);
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getAutoTimestampStorageArray()
        );
    }
}
