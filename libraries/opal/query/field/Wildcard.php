<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

class Wildcard implements opal\query\IWildcardField, core\IDumpable {
    
    protected $_source;
    
    public function __construct(opal\query\ISource $source) {
        $this->_source = $source;
    }
    
    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }
    
    public function getQualifiedName() {
        return $this->getSourceAlias().'.*';
    }
    
    public function getName() {
        return '*';
    }
    
    public function getAlias() {
        return '*';
    }
    
    public function hasDiscreetAlias() {
        return false;
    }
    
    public function dereference() {
        return array($this);
    }
    
    public function isOutputField() {
        return true;
    }
    
// Dump
    public function getDumpProperties() {
        return $this->getQualifiedName();
    }
}
