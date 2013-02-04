<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\schema;

use df;
use df\core;
use df\opal;

abstract class Primitive implements IPrimitive {
    
    use TField;
    
    private $_type;
    
    public function __construct(IField $field) {
        $this->_setName($field->getName());
        $this->_isNullable = $field->isNullable();
        $this->setDefaultValue($field->getDefaultValue());
        $this->_comment = $field->getComment();
    }
    
    public function getType() {
        if(!$this->_type) {
            $parts = explode('\\', get_class($this));
            $parts = explode('_', array_pop($parts), 2);
            $this->_type = array_pop($parts);
        }
        
        return $this->_type;
    }
}




class Primitive_Binary extends Primitive implements ILengthRestrictedField {
    
    use TField_LengthRestricted;
    
    public function __construct(IField $field, $length) {
        parent::__construct($field);
        $this->setLength($length);
    }
    
    protected function _getDefaultLength() {
        return 255;
    }
}



class Primitive_Bit extends Primitive implements IBitSizeRestrictedField {
    
    use TField_BitSizeRestricted;
    
    public function __construct(IField $field, $size) {
        parent::__construct($field);
        $this->setBitSize($size);
    }
}



class Primitive_Blob extends Primitive implements ILargeByteSizeRestrictedField {
    
    use TField_LargeByteSizeRestricted;
    
    public function __construct(IField $field, $size=16) {
        parent::__construct($field);
        $this->setExponentSize($size);
    }
}



class Primitive_Boolean extends Primitive {}



class Primitive_Char extends Primitive implements 
    ILengthRestrictedField, 
    ICharacterSetAwareField {
    
    use TField_LengthRestricted;
    use TField_CharacterSetAware;
    
    public function __construct(IField $field, $length) {
        parent::__construct($field);
        $this->setLength($length);
    }
}



class Primitive_Currency extends Primitive {}
class Primitive_DataObject extends Primitive_Blob {}
class Primitive_Date extends Primitive {}
class Primitive_DateTime extends Primitive {}



class Primitive_Enum extends Primitive implements 
    IOptionProviderField, 
    ICharacterSetAwareField {
    
    use TField_OptionProvider;
    use TField_CharacterSetAware;
    
    public function __construct(IField $field, array $options) {
        parent::__construct($field);
        $this->setOptions($options);
    }
}
    
    
    
class Primitive_Float extends Primitive implements IFloatingPointNumericField {
    
    use TField_FloatingPointNumeric;
    
    public function __construct(IField $field, $precision, $scale) {
        parent::__construct($field);
        $this->setPrecision($precision);
        $this->setScale($scale);
    }
}

class Primitive_Decimal extends Primitive_Float {}



class Primitive_Guid extends Primitive {

    const UUID1 = 1;
    const UUID4 = 2;
    const COMB = 3;
    
    protected $_generator = self::COMB;
    
    public function __construct(IField $field, $generator=self::COMB) {
        parent::__construct($field);
        $this->setGenerator($generator);
    }
    
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
}



class Primitive_Integer extends Primitive implements 
    IByteSizeRestrictedField, 
    IAutoIncrementableField {
    
    use TField_ByteSizeRestricted;
    use TField_Numeric;
    use TField_AutoIncrementable;
    
    public function __construct(IField $field, $size=null) {
        parent::__construct($field);
        $this->setByteSize($size);
    }
}
    
    
    
class Primitive_MultiField extends Primitive implements IMultiFieldPrimitive {
    
    protected $_primitives = array();
    
    public function __construct(IField $field, array $primitives) {
        parent::__construct($field);
        
        foreach($primitives as $name => $primitive) {
            if(!$primitive instanceof IPrimitive) {
                throw new InvalidArgumentException(
                    'Invalid primitive'
                );  
            }

            $primitive->_setName($name);
        }

        $this->_primitives = $primitives;
    }
    
    public function getPrimitives() {
        return $this->_primitives;
    }
}



class Primitive_Null extends Primitive {}
class Primitive_Set extends Primitive_Enum {}



class Primitive_Text extends Primitive implements 
    ILargeByteSizeRestrictedField, 
    ICharacterSetAwareField {
    
    use TField_LargeByteSizeRestricted;
    use TField_CharacterSetAware;
    
    public function __construct(IField $field, $size=16) {
        parent::__construct($field);
        $this->setExponentSize($size);
    }
}
    
    
    
class Primitive_Time extends Primitive {}



class Primitive_Timestamp extends Primitive implements IAutoTimestampField {
    
    use TField_AutoTimestamp;
}



class Primitive_Varbinary extends Primitive_Binary {}
class Primitive_Varchar extends Primitive_Char {}
class Primitive_Year extends Primitive {}