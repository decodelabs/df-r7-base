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

class Date extends Base {
    
    protected $_includeTime = true;
    
    protected function _init($includeTime=true) {
        $this->shouldIncludeTime($includeTime);
    }
    
    public function shouldIncludeTime($flag=null) {
        if($flag !== null) {
            $flag = (bool)$flag;
            
            if($flag !== $this->_includeTime) {
                $this->_hasChanged = true;
            }
            
            $this->_includeTime = $flag;
            return $this;
        }
        
        return $this->_includeTime;
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, $forRecord) {
        if(isset($row[$key])) { 
            return core\time\Date::factory($row[$key]);
        } else {
            return null;
        } 
    }
    
    public function deflateValue($value) {
        $value = $this->sanitizeValue($value, true);
        
        if(empty($value)) {
            return null;
        }
        
        if($this->_includeTime) {
            return $value->format(core\time\Date::DB);
        } else {
            return $value->format(core\time\Date::DBDATE);
        }
    }
    
    public function sanitizeValue($value, $forRecord) {
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
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        if($this->_includeTime) {
            return new opal\schema\Primitive_DateTime($this);
        } else {
            return new opal\schema\Primitive_Date($this);
        } 
    }
}
