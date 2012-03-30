<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\schema;

use df;
use df\core;
use df\opal;

trait TField {
    
    protected $_name;
    protected $_isNullable = false;
    protected $_defaultValue;
    protected $_comment;
    protected $_hasChanged = false;
    
    /*
    public function __construct($name, array $args=array()) {
        $this->_setName($name);
        
        if(method_exists($this, '_init')) {
            call_user_func_array(array($this, '_init'), $args);    
        }
    }
    */
    
    public function _setName($name) {
        if($name != $this->_name) {
            $this->_hasChanged = true;
        }
        
        $this->_name = $name;
        return $this;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function setComment($comment) {
        if($comment != $this->_comment) {
            $this->_hasChanged = true;
        }
        
        $this->_comment = $comment;
        return $this;
    }
    
    public function getComment() {
        return $this->_comment;
    }
    
    public function isNullable($flag=null) {
        if($flag !== null) {
            if((bool)$flag != $this->_isNullable) {
                $this->_hasChanged = true;
            }
            
            $this->_isNullable = (bool)$flag;
            return $this;
        }
        
        return $this->_isNullable;
    }
    
    public function setDefaultValue($value) {
        if($value != $this->_defaultValue) {
            $this->_hasChanged = true;
        }
        
        $this->_defaultValue = $value;
        return $this;
    }
    
    public function getDefaultValue() {
        return $this->_defaultValue;
    }
    
    public function hasChanged() {
        return $this->_hasChanged;
    }
    
    public function acceptChanges() {
        $this->_hasChanged = false;
        return $this;
    }
}






trait TField_CharacterSetAware {
    
    protected $_characterSet;
    
    public function setCharacterSet($charset) {
        if($charset != $this->_characterSet) {
            $this->_hasChanged = true;
        }
        
        $this->_characterSet = $charset;
        return $this;
    }
    
    public function getCharacterSet() {
        return $this->_characterSet;
    }
}



trait TField_BinaryCollationProvider {
    
    protected $_binaryCollation = false;
    
    public function hasBinaryCollation($flag=null) {
        if($flag !== null) {
            if((bool)$flag != $this->_binaryCollation) {
                $this->_hasChanged = true;
            }
            
            $this->_binaryCollation = (bool)$flag;
            return $this;
        }
        
        return $this->_binaryCollation;
    }
}


trait TField_LengthRestricted {
    
    protected $_length;
    
    public function setLength($length) {
        if($length === null) {
            $length = $this->_getDefaultLength($length);
        }
        
        $this->_hasChanged = true;
        $this->_length = $length === null ? null : (int)$length;
        return $this;
    }
    
    public function getLength() {
        return $this->_length;
    }
    
    protected function _getDefaultLength() {
        return null;
    }
}



trait TField_Numeric {
    
    protected $_isUnsigned = false;
    protected $_zerofill = false;
    
    public function isUnsigned($flag=null) {
        if($flag !== null) {
            if((bool)$flag != $this->_isUnsigned) {
                $this->_hasChanged = true;
            }
            
            $this->_isUnsigned = (bool)$flag;
            return $this;
        }
        
        return $this->_isUnsigned;
    }
    
    public function shouldZerofill($flag=null) {
        if($flag !== null) {
            if((bool)$flag != $this->_zerofill) {
                $this->_hasChanged = true;
            }
            
            $this->_zerofill = (bool)$flag;
            return $this;
        }
        
        return $this->_zerofill;
    }
}



trait TField_FloatingPointNumeric {
    
    use TField_Numeric;
    
    protected $_precision;
    protected $_scale;
    
    public function setPrecision($precision) {
        $this->_precision = $precision === null ? null : (int)$precision;
        $this->_hasChanged = true;
        return $this;
    }
    
    public function getPrecision() {
        return $this->_precision;
    }
    
    public function setScale($scale) {
        $this->_scale = $scale === null ? null : (int)$scale;
        $this->_hasChanged = true;
        return $this;
    }
    
    public function getScale() {
        return $this->_scale;
    }
}



trait TField_AutoIncrementable {
    
    protected $_autoIncrement = false;
    
    public function shouldAutoIncrement($flag=null) {
        if($flag !== null) {
            if((bool)$flag != $this->_autoIncrement) {
                $this->_hasChanged = true;
            }
            
            $this->_autoIncrement = (bool)$flag;
            return $this;
        }
        
        return $this->_autoIncrement;
    }
}



trait TField_AutoTimestamp {
    
    protected $_shouldTimestampOnUpdate = false;
    protected $_shouldTimestampAsDefault = true;
    
    public function shouldTimestampOnUpdate($flag=null) {
        if($flag !== null) {
            if((bool)$flag != $this->_shouldTimestampOnUpdate) {
                $this->_hasChanged = true;
            }
            
            $this->_shouldTimestampOnUpdate = (bool)$flag;
            return $this;
        }
        
        return $this->_shouldTimestampOnUpdate;
    }
    
    public function shouldTimestampAsDefault($flag=null) {
        if($flag !== null) {
            if((bool)$flag != $this->_shouldTimestampAsDefault) {
                $this->_hasChanged = true;
            }
            
            if($this->_shouldTimestampAsDefault = (bool)$flag) {
                $this->setDefaultValue(null);
            }
            
            return $this;
        }
        
        return $this->_shouldTimestampAsDefault;
    }
    
    public function setDefaultValue($value) {
        if($value !== null) {
            $this->_shouldTimestampAsDefault = false;
        }
        
        return parent::setDefaultValue($value);
    }
}





trait TField_OptionProvider {
    
    protected $_options = array();
    
    public function setOptions(array $options) {
        $this->_options = $options;
        $this->_hasChanged = true;
        return $this;
    }
    
    public function getOptions() {
        return $this->_options;
    }
}




trait TField_BitSizeRestricted {
    
    protected $_bitSize;
    
    public function setBitSize($size) {
        $newSize = $this->_normalizeBits($size);
        
        if($newSize != $this->_bitSize) {
            $this->_hasChanged = true;
        }
        
        $this->_bitSize = $newSize;
        return $this;
    }
    
    public function getBitSize() {
        return $this->_bitSize;
    }
    
    protected function _normalizeBits($size) {
        if(is_string($size)) {
            switch(strtolower($size)) {
                case opal\schema\IFieldSize::TINY:
                    $size = 1;
                    break;
                    
                case opal\schema\IFieldSize::SMALL:
                    $size = 8;
                    break;
                    
                case opal\schema\IFieldSize::MEDIUM:
                    $size = 16;
                    break;
                    
                case opal\schema\IFieldSize::LARGE:
                    $size = 32;
                    break;
                    
                case opal\schema\IFieldSize::HUGE:
                    $size = 64;
                    break;
                    
                default:
                    if(is_numeric($size)) {
                        $size = (int)$size;
                    } else{
                        $size = 16;
                    }
                    
                    break;
            }
        }
        
        $size = (int)$size;
        
        if($size > 64) {
            throw new opal\schema\InvalidArgumentException(
                'Maximum bit size is 64'
            );
        } else if($size < 1) {
            throw new opal\schema\InvalidArgumentException(
                'Minimum bit size is 1'
            );
        }
        
        return $size;
    }
}

trait TField_ByteSizeRestricted {
    
    protected $_byteSize;
    
    public function setByteSize($size) {
        $newSize = $this->_normalizeBytes($size);
        
        if($newSize != $this->_byteSize) {
            $this->_hasChanged = true;
        }
        
        $this->_byteSize = $newSize;
        return $this;
    }
    
    public function getByteSize() {
        return $this->_byteSize;
    }
    
    private function _normalizeBytes($size) {
        if(is_string($size)) {
            switch(strtolower($size)) {
                case opal\schema\IFieldSize::TINY:
                    $size = 1;
                    break;
                    
                case opal\schema\IFieldSize::SMALL:
                    $size = 2;
                    break;
                    
                case opal\schema\IFieldSize::MEDIUM:
                    $size = 3;
                    break;
                    
                case opal\schema\IFieldSize::LARGE:
                    $size = 4;
                    break;
                    
                case opal\schema\IFieldSize::HUGE:
                    $size = 8;
                    break;
                    
                default:
                    if(is_numeric($size)) {
                        $size = (int)$size;
                    } else {
                        $size = 4;
                    }
                    
                    break;
            }
        }
        
        $size = (int)$size;
        
        switch($size) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 8:
                break;
                
            default:
                if($size < 8) {
                    $size = 8;
                } else {
                    throw new opal\schema\InvalidArgumentException(
                        'Maximum byte size is 8'
                    );
                }
        }
        
        return $size;
    }
}


trait TField_LargeByteSizeRestricted {
    
    protected $_exponentSize;
    
    public function setExponentSize($size) {
        $newSize = $this->_normalizeExponent($size);
        
        if($newSize != $this->_exponentSize) {
            $this->_hasChanged = true;
        }
        
        $this->_exponentSize = $newSize;
        
        return $this;
    }
    
    public function getExponentSize() {
        return $this->_exponentSize;
    }
    
    protected function _normalizeExponent($size) {
        if(is_string($size)) {
            switch(strtolower($size)) {
                case opal\schema\IFieldSize::TINY:
                case opal\schema\IFieldSize::SMALL:
                    $size = 8;
                    break;
                    
                case opal\schema\IFieldSize::MEDIUM:
                    $size = 16;
                    break;
                    
                case opal\schema\IFieldSize::LARGE:
                    $size = 24;
                    break;
                    
                case opal\schema\IFieldSize::HUGE:
                    $size = 32;
                    break;
                    
                default:
                    if(is_numeric($size)) {
                        $size = (int)$size;
                    } else {
                        $size = 16;
                    }
                    
                    break;
            }
        }
        
        $size = (int)$size;
        
        switch($size) {
            case 8:
            case 16:
            case 24:
            case 32:
                break;
                
            default:
                if($size < 8) {
                    $size = 8;
                } else if($size < 16) {
                    $size = 16;
                } else if($size < 24) {
                    $size = 24;
                } else if($size < 32) {
                    $size = 32;
                } else {
                    throw new opal\schema\InvalidArgumentException(
                        'Maximum exponent byte size is 2 ^ 32'
                    );
                }
        }
        
        return $size;
    }
}
