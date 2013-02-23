<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

class Virtual implements opal\query\IVirtualField, core\IDumpable {
    
    use opal\query\TField;
    
    protected $_name;
    protected $_alias;
    protected $_targetFields = array();
    protected $_source;
    
    public function __construct(opal\query\ISource $source, $name, $alias=null, array $targetFields=array()) {
        $this->_source = $source;
        $this->_name = $name;
        
        if($alias === null) {
            $alias = $name;
        }
        
        $this->_alias = $alias;
        $this->_targetFields = $targetFields;
    }
    
    
    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }
    
    public function getQualifiedName() {
        return $this->getSourceAlias().'.'.$this->getName();
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function getAlias() {
        return $this->_alias;
    }
    
    public function hasDiscreetAlias() {
        return $this->getAlias() !== $this->getName();
    }
    
    public function getTargetFields() {
        return $this->_targetFields;
    }
    
    public function dereference() {
        $output = array();

        foreach($this->_targetFields as $key => $field) {
            if($field instanceof opal\query\IVirtualField) {
                $output = array_merge($output, $field->dereference());
            } else {
                $output[$key] = $field;
            }
        }

        return $output;
    }

    public function isOutputField() {
        return $this->_source->isOutputField($this);
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
