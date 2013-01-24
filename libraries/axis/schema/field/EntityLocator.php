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
    
class EntityLocator extends Base {

// Values
    public function inflateValueFromRow($key, array $row, opal\query\record\IRecord $forRecord=null) {
        if(!isset($row[$key])) {
            return null;
        }

        return core\policy\EntityLocator::factory($row[$key]);
    }

    public function deflateValue($value) {
        $value = $this->sanitizeValue($value, true);

        if(empty($value)) {
            return null;
        }

        return (string)$value;
    }

    public function sanitizeValue($value, $forRecord) {
        if(empty($value)) {
            if($this->isNullable()) {
                return null;
            } else if(!empty($this->_defaultValue)) {
                $value = $this->_defaultValue;
            } else {
                throw new axis\schema\UnexpectedValueException(
                    'This field cannot be null'
                );
            }
        }

        return core\policy\EntityLocator::factory($value);
    }

    public function compareValues($value1, $value2) {
        return (string)$value1 === (string)$value2;
    }


// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Varchar($this, 255);
    }
}