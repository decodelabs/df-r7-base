<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class ImplicitSourceTransaction extends Transaction {
    
    protected $_source;
    
    public function __construct(core\IApplication $application, $source) {
        parent::__construct($application);
        $this->_source = $source;
    }
    
    public function select($field1=null) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginSelect(func_get_args())
            ->from($this->_source);
    }
    
    public function fetch() {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginFetch()
            ->from($this->_source);
    }
    
    public function insert($row) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginInsert($row)
            ->into($this->_source);
    }
    
    public function batchInsert($rows=array()) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginBatchInsert($rows)
            ->into($this->_source);
    }
    
    public function replace($row) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginReplace($row)
            ->in($this->_source);
    }
    
    public function batchReplace($rows=array()) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginBatchReplace($rows)
            ->in($this->_source);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginUpdate($valueMap)
            ->in($this->_source);
    }
    
    public function delete() {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginDelete()
            ->from($this->_source);
    }
    
    public function begin() {
        return new self($this->_application, $this->_source);
    }
}
