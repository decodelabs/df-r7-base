<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed;

use df;
use df\core;
use df\spur;

class Category implements ICategory {
    
    protected $_term;
    protected $_scheme;
    protected $_label;
    protected $_children;
    
    public function __construct($term=null, $scheme=null, $label=null) {
        $this->setTerm($term);
        $this->setScheme($scheme);
        $this->setLabel($label);
    }
    
    public function setTerm($term) {
        $this->_term = trim($term);
        
        if(!strlen($this->_term)) {
            $this->_term = null;
        }
        
        return $this;
    }
    
    public function getTerm() {
        return $this->_term;
    }
    
    public function hasTerm() {
        return $this->_term !== null;
    }
    
    public function setScheme($scheme) {
        $this->_scheme = trim($scheme);
        
        if(!strlen($this->_scheme)) {
            $this->_scheme = null;
        }
        
        return $this;
    }
    
    public function getScheme() {
        return $this->_scheme;
    }
    
    public function hasScheme() {
        return $this->_scheme !== null;
    }
    
    public function setLabel($label) {
        $this->_label = trim($label);
        
        if(!strlen($this->_label)) {
            $this->_label = null;
        }
        
        return $this;
    }
    
    public function getLabel() {
        return $this->_label;
    }
    
    public function hasLabel() {
        return $this->_label !== null;
    }
    
    public function setChildren(array $children) {
        $this->_children = $children;
        return $this;
    }
    
    public function getChildren() {
        return $this->_children;
    }
    
    public function hasChildren() {
        return !empty($this->_children);
    }
}