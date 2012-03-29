<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class OrderDirective implements IOrderDirective, core\IDumpable {
    
    protected $_isDescending = false;
    protected $_field;
    
    public function __construct(opal\query\IField $field, $direction=null) {
        $this->setField($field);
        $this->setDirection($direction);
    }
    
    public function setField(opal\query\IField $field) {
        $this->_field = $field;
        return $this;
    }
    
    public function getField() {
        return $this->_field;
    }
    
    public function setDirection($direction) {
        if(is_string($direction)) {
            switch(strtolower($direction)) {
                case 'desc':
                case 'd':
                    $direction = true;
                    break;
                    
                default:
                    $direction = false;
            }
        }
        
        $this->_isDescending = (bool)$direction;
        return $this;
    }
    
    public function isDescending($flag=null) {
        if($flag !== null) {
            $this->_isDescending = (bool)$flag;
            return $this;
        }
        
        return $this->_isDescending;
    }
    
    public function isAscending($flag=null) {
        if($flag !== null) {
            $this->_isDescending = !(bool)$flag;
            return $this;
        }
        
        return !$this->_isDescending;
    }
    
    public function toString() {
        return $this->_field->getQualifiedName().' '.($this->_isDescending ? 'DESC' : 'ASC');
    }
    
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return '';
        }
    }
    
// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}
