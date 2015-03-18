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


// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        if(isset($row[$key])) { 
            return (int)$row[$key];
        } else {
            return null;
        } 
    }
    

    public function compareValues($value1, $value2) {
        if(is_numeric($value1)) {
            $value1 = (int)$value1;
        }

        if(is_numeric($value2)) {
            $value2 = (int)$value2;
        }

        return $value1 === $value2;
    }

    public function getSearchFieldType() {
        return 'integer';
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
    
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setByteSizeRestrictedStorageArray($data);
        $this->_setNumericStorageArray($data);
        $this->_setAutoIncrementStorageArray($data);
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getByteSizeRestrictedStorageArray(),
            $this->_getNumericStorageArray(),
            $this->_getAutoIncrementStorageArray()
        );
    }
}
