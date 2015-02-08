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
    
    public function __construct($source) {
        $this->_source = $source;
    }
    
    public function select($field1=null) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginSelect(func_get_args())
            ->from($this->_source);
    }

    public function selectDistinct($field1=null) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginSelect(func_get_args(), true)
            ->from($this->_source);
    }

    public function countAll() {
        return $this->select()->count();
    }

    public function countAllDistinct() {
        return $this->selectDistinct()->count();
    }

    public function union() {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginUnion()
            ->with(func_get_args())
            ->from($this->_source);
    }
    
    public function fetch() {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginFetch()
            ->from($this->_source);
    }
    
    public function insert($row) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginInsert($row)
            ->into($this->_source);
    }
    
    public function batchInsert($rows=[]) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginBatchInsert($rows)
            ->into($this->_source);
    }
    
    public function replace($row) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginReplace($row)
            ->in($this->_source);
    }
    
    public function batchReplace($rows=[]) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginBatchReplace($rows)
            ->in($this->_source);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginUpdate($valueMap)
            ->in($this->_source);
    }
    
    public function delete() {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginDelete()
            ->from($this->_source);
    }
    
    public function begin() {
        return new self($this->_source);
    }
}
