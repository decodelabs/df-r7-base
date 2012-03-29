<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

class Attachment implements opal\query\IAttachmentField, core\IDumpable {
    
    protected $_name;
    protected $_attachment;
    
    public function __construct($name, opal\query\IAttachQuery $attachment) {
        $this->_name = $name;
        $this->_attachment = $attachment;
    }
    
    public function getSource() {
        return $this->_attachment->getSource();
    }
    
    public function getSourceAlias() {
        return $this->_attachment->getSourceAlias();
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function getQualifiedName() {
        return $this->_name;
    }
    
    public function getAlias() {
        return $this->_name;
    }
    
    public function hasDiscreetAlias() {
        return false;
    }
    
    public function dereference() {
        return array($this);
    }
    
// Dump
    public function getDumpProperties() {
        return 'attach('.$this->getQualifiedName().')';
    }
}
