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

class Boolean extends Base {
    
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        return $this->sanitizeValue(
            isset($row[$key]) ? $row[$key] : null, 
            $forRecord
        );
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if($value === null && !$this->isNullable()) {
            $value = (bool)$this->_defaultValue;
        }

        if($value !== null) {
            $value = (bool)$value;
        }

        return $value;
    }

    public function compareValues($value1, $value2) {
        if($value1 !== null) {
            $value1 = (bool)$value1;
        }

        if($value2 !== null) {
            $value2 = (bool)$value2;
        }

        return $value1 === $value2;
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        //return new opal\schema\Primitive_Bit($this, 1);
        return new opal\schema\Primitive_Boolean($this);
    }
}
