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

class Guid extends Base implements axis\schema\IAutoGeneratorField {
    
    use axis\schema\TAutoGeneratorField;
    
    const UUID1 = 1;
    const UUID4 = 2;
    const COMB = 3;
    
    protected $_generator = self::COMB;
    
    public function setGenerator($gen) {
        if(is_string($gen)) {
            switch(strtolower($gen)) {
                case 'uuid':
                case 'uuid4':
                    $gen = self::UUID4;
                    break;
                    
                case 'uuid1':
                    $gen = self::UUID1;
                    break;
                    
                case 'comb':
                    $gen = self::COMB;
                    break;
            }
        }
        
        switch($gen) {
            case self::UUID1:
            case self::UUID4:
                break;
                
            case self::COMB:
            default:
                $gen = self::COMB;
                break;
        }
        
        if($this->_generator !== $gen) {
            $this->_hasChanged = true;
        }
        
        $this->_generator = $gen;
        
        return $this;
    }
    
    public function getGenerator() {
        return $this->_generator;
    }
    
    public function getGeneratorName() {
        switch($this->_generator) {
            case self::UUID1:
                return 'UUID v1';
                
            case self::UUID4:
                return 'UUID v4';
                
            case self::COMB:
                return 'Comb';
        }
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, opal\query\record\IRecord $forRecord=null) {
        if(isset($row[$key])) { 
            return core\string\Uuid::factory($row[$key]);
        } else {
            return null;
        } 
    }

    public function deflateValue($value) {
        if($value === null) {
            return null;
        }
        
        return $value->getBytes();
    }
    
    public function sanitizeValue($value, $forRecord) {
        return core\string\Uuid::factory($value);
    }

    public function compareValues($value1, $value2) {
        return (string)$value1 === (string)$value2;
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
        
        switch($this->_generator) {
            case self::UUID1:
                return core\string\Uuid::v1();
                
            case self::UUID4:
                return core\string\Uuid::v4();
                
            case self::COMB:
                return core\string\Uuid::comb();
        }
    }
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Guid($this, $this->_generator);
    }
    
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_generator = $data['gen'];
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            ['gen' => $this->_generator]
        );
    }
    
// Dump
    public function getFieldTypeDisplayName() {
        return 'Guid ['.$this->getGeneratorName().']';
    }
}
