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

class DateTime extends Base implements axis\schema\IDateField {
    
    
// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        if(isset($row[$key])) { 
            return core\time\Date::factory($row[$key]);
        } else {
            return null;
        } 
    }
    
    public function deflateValue($value) {
        $value = $this->sanitizeValue($value);
        
        if(empty($value)) {
            return null;
        }
        
        return $value->format(core\time\Date::DB);
    }
    
    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if(empty($value)) {
            if($this->isNullable()) {
                return null;
            } else if(!empty($this->_defaultValue)) {
                $value = $this->_defaultValue;
            } else {
                $value = 'now';
            }
        }
        
        $value = core\time\Date::factory($value);
        $value->toUtc();
        
        return $value;
    }

    public function compareValues($value1, $value2) {
        if($value1 === null || $value2 === null) {
            return $value1 === $value2;
        }

        return core\time\Date::factory($value1)->eq($value2);
    }
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_DateTime($this);
    }
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
    }

    public function toStorageArray() {
        return $this->_getBaseStorageArray();
    }
}
