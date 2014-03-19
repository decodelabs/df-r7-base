<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

class Expression implements opal\query\IExpressionField {
    
    use opal\query\TField;

    protected $_expression;
    protected $_alias;
    protected $_source;
    protected $_altSourceAlias;

    public function __construct(opal\query\ISource $source, $expression, $alias=null) {
        if($alias === null) {
            $alias = uniqid('expr');
        }

        $this->_expression = $expression;
        $this->_alias = $alias;
        $this->_source = $source;
    }

    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }

    public function setAltSourceAlias($alias) {
        $this->_altSourceAlias = $alias;
        return $this;
    }

    public function getAltSourceAlias() {
        return $this->_altSourceAlias;
    }
    
    public function getExpression() {
        return $this->_expression;
    }

    public function isNull() {
        return $this->_expression === null;
    }

    public function getName() {
        if($this->_expression) {
            return (string)$this->_expression;
        } else {
            return '#NULL';
        }
    }

    public function setAlias($alias) {
        $this->_alias = $alias;
        return $this;
    }

    public function getAlias() {
        return $this->_alias;
    }

    public function hasDiscreetAlias() {
        return $this->_alias !== $this->_name;
    }

    public function getQualifiedName() {
        if($this->_altSourceAlias) {
            return $this->_altSourceAlias.'.'.$this->_alias;
        } else {
            return $this->getSourceAlias().'.'.$this->_alias;
        }
    }

    public function dereference() {
        return [$this];
    }

    public function isOutputField() {
        return $this->_source->isOutputField($this);
    }

    public function rewriteAsDerived(opal\query\ISource $source) {
        core\stub($source);
    }
}