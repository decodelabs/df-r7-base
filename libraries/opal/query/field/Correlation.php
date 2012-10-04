<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;
    
class Correlation implements opal\query\ICorrelationField, core\IDumpable {

    protected $_query;

    public function __construct(opal\query\ICorrelationQuery $query) {
        $this->_query = $query;
    }

    public function getSource() {
        return $this->_query->getSource();
    }

    public function getSourceAlias() {
        return $this->_query->getSourceAlias();
    }

    public function getCorrelationQuery() {
        return $this->_query;
    }

    public function getQualifiedName() {
        return $this->_query->getFieldAlias();
        //return 'CORRELATION('.$this->getSourceAlias().'.'.$this->getAlias().')';
    }

    public function getName() {
        return $this->getAlias();
    }

    public function getAlias() {
        return $this->_query->getFieldAlias();
    }

    public function hasDiscreetAlias() {
        return false;
    }

    public function dereference() {
        return [$this];
    }

// Dump
    public function getDumpProperties() {
        return [$this->getAlias() => $this->_query];
    }
}