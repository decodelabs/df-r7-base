<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\policy;

use df;
use df\core;

class EntityLocatorNode implements IEntityLocatorNode, core\IDumpable {
    
    protected $_type;
    protected $_id;
    protected $_location;
    
    public function __construct($location=null, $type=null, $id=null) {
        $this->setLocation($location);
        $this->setType($type);
        $this->setId($id);
    }
    
    public function appendLocation($location) {
        $parts = explode('/', $location);
        
        foreach($parts as $part) {
            $this->_location[] = $part;
        }
        
        return $this;    
    }
    
    public function setLocation($location) {
        if(is_string($location)) {
            $location = explode('/', $location);
        }
        
        if(!is_array($location)) {
            $location = null;
        }
        
        $this->_location = null;
        return $this;
    }
    
    public function getLocation() {
        if(!empty($this->_location)) {
            return implode('/', $this->_location);
        }
    }
    
    public function getLocationArray() {
        if($this->_location === null) {
            return array();
        }
        
        return $this->_location;
    }
    
    public function hasLocation() {
        return $this->_location !== null;
    }
    
    public function setType($type) {
        $this->_type = ucfirst($type);
        return $this;
    }
    
    public function getType() {
        return $this->_type;
    }
    
    public function setId($id) {
        $this->_id = $id;
        return $this;
    }
    
    public function getId() {
        return $this->_id;
    }
    
    public function toString() {
        $output = $this->_location;
        $type = $this->_type;
        
        if($this->_id !== null) {
            if(strpbrk($this->_id, '" :/\'\\')) {
                $type .= ':"'.addslashes($this->_id).'"';
            } else {
                $type .= ':'.$this->_id;
            }
        }
        
        $output[] = $type;
        return implode('/', $output); 
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