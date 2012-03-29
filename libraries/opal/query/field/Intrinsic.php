<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

class Intrinsic implements opal\query\IIntrinsicField, core\IDumpable {
    
    protected $_name;
    protected $_alias;
    protected $_source;
    
    public function __construct(opal\query\ISource $source, $name, $alias=null) {
        $this->_source = $source;
        $this->_name = $name;
        
        if($alias === null) {
            $alias = $name;
        }
        
        $this->_alias = $alias;
    }
    
    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }
    
    public function getQualifiedName() {
        return $this->getSourceAlias().'.'.$this->_name;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function getAlias() {
        return $this->_alias;
    }
    
    public function hasDiscreetAlias() {
        return $this->_alias !== $this->_name;
    }
    
    public function dereference() {
        return array($this);
    }
    
// Dump
    public function getDumpProperties() {
        $output = $this->getQualifiedName();
        
        if($this->hasDiscreetAlias()) {
            $output .= ' as '.$this->getAlias();
        }

        return $output;
    }
}
