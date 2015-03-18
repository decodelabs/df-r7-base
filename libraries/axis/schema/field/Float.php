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
    
class Float extends Base implements opal\schema\IFloatingPointNumericField {

    use opal\schema\TField_FloatingPointNumeric;

    protected function _init($scale=null, $precision=null) {
        $this->setScale($scale);
        $this->setPrecision($precision);
    }

// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        if(isset($row[$key])) { 
            return (double)$row[$key];
        } else {
            return null;
        } 
    }

    public function compareValues($value1, $value2) {
        // Use precision setting to define comparison value
        return abs($value1 - $value2) < 0.00001;
    }

// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = new opal\schema\Primitive_Float($this, $this->_precision, $this->_scale);

        if($this->_isUnsigned) {
            $output->isUnsigned(true);
        }
        
        if($this->_zerofill) {
            $output->shouldZerofill(true);
        }

        return $output;
    }

// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setFloatingPointNumericStorageArray($data);
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getFloatingPointNumericStorageArray()
        );
    }
}