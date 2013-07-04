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

abstract class Base implements axis\schema\IField, \Serializable, core\IDumpable {
    
    use opal\schema\TField;
    
    public static function factory(axis\schema\ISchema $schema, $name, $type, array $args=null) {
        $class = 'df\\axis\\unit\\'.$schema->getUnitType().'\\schema\\field\\'.ucfirst($type);
        
        if(!class_exists($class)) {
            $class = 'df\\axis\\schema\\field\\'.ucfirst($type);
            
            if(!class_exists($class)) {
                throw new axis\schema\FieldTypeNotFoundException(
                    'Field type '.$type.' could not be found'
                );
            }
        }
        
        return new $class($schema, $type, $name, $args);
    }
    
    public function __construct(axis\schema\ISchema $schema, $type, $name, array $args=null) {
        $this->_setName($name);
        
        if($args !== null && method_exists($this, '_init')) {
            call_user_func_array(array($this, '_init'), $args);    
        }
    }
    
    
    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $output = clone $this;
        $output->_defaultValue = null;
        $output->_isNullable = false;
        
        if($output instanceof opal\schema\IAutoIncrementableField) {
            $output->shouldAutoIncrement(false);
        }
        
        if($output instanceof axis\schema\IAutoGeneratorField) {
            $output->shouldAutoGenerate(false);
        }
        
        return $output;
    }


// Serialize
    public function serialize() {
        return json_encode($this->toStorageArray());
    }

    public function unserialize($data) {
        $data = json_decode($data, true);
        $this->_setName($data['nam']);
        $this->_importStorageArray($data);

        return $this;
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        if(isset($row[$key])) {
            return $row[$key];
        } else {
            return $this->_defaultValue;
        }
    }
    
    public function deflateValue($value) {
        return $value;
    }
    
    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        return $value;
    }
    
    public function normalizeSavedValue($value, opal\record\IRecord $forRecord=null) {
        return $value;
    }

    public function generateInsertValue(array $row) {
        if($this->_defaultValue !== null) {
            return $this->_defaultValue;
        } else if($this->isNullable()) {
            return null;
        } else {
            return '';
        }
    }
    
    public function compareValues($value1, $value2) {
        return $value1 == $value2;
    }
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return $this;
    }
    
    
// Ext. serialize
    public static function fromStorageArray(axis\schema\ISchema $schema, array $data) {
        $output = self::factory($schema, $data['nam'], $data['typ'], null);
        $output->_importStorageArray($data);
        
        return $output;
    }

    public function toStorageArray() {
        return $this->_getBaseStorageArray();
    }
    
    protected function _setBaseStorageArray(array $data) {
        $this->_setGenericStorageArray($data);
    }
    
    protected function _getBaseStorageArray() {
        return $this->_getGenericStorageArray();
    }
    
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->getFieldSchemaString();
    }
    
    public function getFieldTypeDisplayName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
    
    public function getFieldSchemaString() {
        $type = $this->getFieldTypeDisplayName();
        $output = $this->_name.' '.$type;
        
        if($this instanceof opal\schema\ILengthRestrictedField) {
            if(null !== ($length = $this->getLength())) {
                $output .= '('.$length.')';
            }
        } else if($this instanceof opal\schema\IBitSizeRestrictedField) {
            $output .= '('.$this->getBitSize().' bits)';
        } else if($this instanceof opal\schema\IByteSizeRestrictedField) {
            $output .= '('.$this->getByteSize().' bytes)';
        } else if($this instanceof opal\schema\ILargeByteSizeRestrictedField) {
            $output .= '(2 ^ '.$this->getExponentSize().' bytes)';
        }
        
        if($this->_isNullable) {
            $output .= ' NULL';
        }
        
        if($this instanceof opal\schema\IAutoTimestampField && $this->shouldTimestampAsDefault()) {
            $output .= ' DEFAULT now';
        } else if($this->_defaultValue !== null) {
            $output .= ' DEFAULT \''.$this->_defaultValue.'\'';
        }
        
        if($this instanceof opal\schema\IAutoTimestampField && $this->shouldTimestampOnUpdate()) {
            $output .= ' TIMESTAMP_ON_UPDATE';
        }

        if($this instanceof opal\schema\ICharacterSetAwareField &&$this->_characterSet !== null) {
            $output .= ' CHARSET '.$this->_characterSet;
        }
        
        return $output;
    }
}
