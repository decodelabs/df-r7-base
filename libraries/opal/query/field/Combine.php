<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

class Combine implements opal\query\ICombineField, core\IDumpable {

    use opal\query\TField;

    protected $_name;
    protected $_combine;

    public function __construct($name, opal\query\ICombineQuery $combine) {
        $this->_name = $name;
        $this->_combine = $combine;
    }

    public function getSource() {
        return $this->_combine->getSource();
    }

    public function getSourceAlias() {
        return $this->_combine->getSourceAlias();
    }

    public function getName() {
        return $this->_name;
    }

    public function getQualifiedName() {
        return $this->_combine->getParentQuery()->getSourceAlias().'.'.$this->_name;
    }

    public function setAlias($alias) {
        $this->_name = $alias;
        return $this;
    }

    public function getAlias() {
        return $this->_name;
    }

    public function hasDiscreetAlias() {
        return false;
    }

    public function dereference() {
        return [$this];
    }

    public function isOutputField() {
        return true;
    }

    public function getCombine() {
        return $this->_combine;
    }

    public function rewriteAsDerived(opal\query\ISource $source) {
        core\stub($source);
    }


    public function toString() {
        return 'combine('.$this->getQualifiedName().', ['.implode(', ', array_keys($this->_combine->getFields())).'])';
    }
}
