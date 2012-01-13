<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Property {
    
    const VIS_PRIVATE = 0;
    const VIS_PROTECTED = 1;
    const VIS_PUBLIC = 2;
    
    protected $_name = null;
    protected $_value;
    protected $_visibility = self::VIS_PUBLIC;
    protected $_deep = false;
    
    public function __construct($name, $value, $visibility=self::VIS_PUBLIC, $deep=false) {
        $this->setName($name);
        $this->_value = $value;
        $this->setVisibility($visibility);
        $this->_deep = (bool)$deep;
    }
    
// Name
    public function setName($name) {
        $this->_name = (string)$name;
        
        if(empty($this->_name) && $this->_name !== '0') {
            $this->_name = null;
        }
        
        return $this;
    }
    
    public function hasName() {
        return $this->_name !== null;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    
// Value
    public function setValue($value) {
        $this->_value = $value;
        return $this;
    }
    
    public function getValue() {
        return $this->_value;
    }
    
    public function inspectValue(Inspector $inspector) {
        if($this->_value instanceof core\debug\IDump) {
            return $this->_value;
        }
        
        return $inspector->inspect($this->_value, $this->_deep);
    }
    
    
// Visibility
    public function setVisibility($visibility) {
        if(is_string($visibility)) {
            switch(strtolower($visibility)) {
                case 'private':
                    $visibility = self::VIS_PRIVATE;
                    break;
                    
                case 'protected':
                    $visibility = self::VIS_PROTECTED;
                    break;
                    
                default:
                    $visibility = self::VIS_PUBLIC;
                    break;
            }
        }
        
        switch($visibility) {
            case self::VIS_PRIVATE:
            case self::VIS_PROTECTED:
            case self::VIS_PUBLIC:
                $this->_visibility = $visibility;
                break;
                
            default:
                $this->_visibility = self::VIS_PUBLIC;
                break;
        }
        
        return $this;
    }
    
    public function getVisibility() {
        return $this->_visibility;
    }
    
    public function getVisibilityString() {
        switch($visibility) {
            case self::VIS_PRIVATE:
                return 'private';
                
            case self::VIS_PROTECTED:
                return 'protected';
                
            case self::VIS_PUBLIC:
                return 'public';
        }
    }
    
    public function isPublic() {
        return $this->_visibility === self::VIS_PUBLIC;
    }
    
    public function isProtected() {
        return $this->_visibility === self::VIS_PROTECTED;
    }
    
    public function isPrivate() {
        return $this->_visibility === self::VIS_PRIVATE;
    }
    
// Deep
    public function isDeep() {
        return $this->_deep;
    }
    
    public function canInline() {
        return !$this->_deep 
            && !$this->hasName() 
            && (is_scalar($this->_value) || is_null($this->_value));
    }
}
