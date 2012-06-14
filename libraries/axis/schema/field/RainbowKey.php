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

class RainbowKey extends Base implements 
    opal\schema\IByteSizeRestrictedField, 
    axis\schema\IAutoGeneratorField {
    
    use opal\schema\TField_ByteSizeRestricted;
    use axis\schema\TAutoGeneratorField;
    
    protected function _init($size=null) {
        $this->setByteSize($size);
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, $forRecord) {
        if(isset($row[$key])) { 
            return core\string\RainbowKey::factory($row[$key]);
        } else {
            return null;
        } 
    }
    
    public function deflateValue($value) {
        if(empty($value)) {
            return null;
        }
        
        if(!$value instanceof core\string\IRainbowKey) {
            $value = $this->sanitizeValue($value, false);
        }
        
        return $value->getBytes();
    }
    
    public function sanitizeValue($value, $forRecord) {
        return core\string\RainbowKey::factory($value, $this->_byteSize);
    }
    
    public function generateInsertValue(array $row) {
        if(!$this->_autoGenerate) {
            return null;
        }
        
        if(array_key_exists($this->_name, $row) && $this->isNullable()) {
            return null;
        }
        
        if($this->_defaultValue !== null) {
            return $this->_defaultValue;
        }
        
        
        if(self::$_genId === null) {
            self::$_genId = mt_rand(0, 0xffff);
        }
        
        
        return core\string\RainbowKey::create(++self::$_counter, self::$_genId, $this->_byteSize);
    }
    
    protected static $_counter = 0;
    protected static $_genId = null;
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        //core\stub('Rainbow keys don\'t yet have ticket sources implemented');
    }
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Binary($this, $this->_byteSize + 2);
    }
    
    
// Ext. serialize
    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getByteSizeRestrictedStorageArray()
        );
    }
}
