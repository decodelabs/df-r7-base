<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Transaction implements ITransaction, core\IDumpable {
    
    protected $_level = 1;
    protected $_adapters = [];
    
    public function select($field1=null) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginSelect(func_get_args());
    }

    public function selectDistinct($field1=null) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginSelect(func_get_args(), true);
    }

    public function union() {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginUnion();
    }
    
    public function fetch() {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginFetch();
    }
    
    public function insert($row) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginInsert($row);
    }
    
    public function batchInsert($rows=[]) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginBatchInsert($rows);
    }
    
    public function replace($row) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginReplace($row);
    }
    
    public function batchReplace($rows=[]) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginBatchReplace($rows);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginUpdate($valueMap);
    }
    
    public function delete() {
        return Initiator::factory()
            ->setTransaction($this)
            ->beginDelete();
    }
    
    public function begin() {
        return new self();
    }
    
    
    public function commit() {
        if($this->_level == 1) {
            foreach($this->_adapters as $adapter) {
                if($adapter->supportsQueryFeature(IQueryFeatures::TRANSACTION)) {
                    $adapter->commitQueryTransaction();
                }
            }
        }
        
        if($this->_level > 0) {
            $this->_level--;
        }
        
        return $this;
    }
    
    public function rollback() {
        if($this->_level == 1) {
            foreach($this->_adapters as $adapter) {
                if($adapter->supportsQueryFeature(IQueryFeatures::TRANSACTION)) {
                    $adapter->rollbackQueryTransaction();
                }
            }
        }
        
        if($this->_level > 0) {
            $this->_level--;
        }
        
        return $this;
    }
    
    public function beginAgain() {
        if(!$this->_level) {
            foreach($this->_adapters as $adapter) {
                if($adapter->supportsQueryFeature(IQueryFeatures::TRANSACTION)) {
                    $adapter->beginQueryTransaction();
                }
            }
        }
        
        $this->_level++;
        
        return $this;
    }
    
    
// Adapters
    public function registerAdapter(IAdapter $adapter, $forWrite=true) {
        $isCapable = $adapter->supportsQueryFeature(IQueryFeatures::TRANSACTION);
        $id = $adapter->getQuerySourceAdapterHash();
        
        if(!isset($this->_adapters[$id])) {
            $this->_adapters[$id] = $adapter;
            
            if($isCapable) {
                $adapter->beginQueryTransaction();
            }
        }
        
        /*
        if(!$isCapable && $forWrite) {
            throw new RuntimeException(
                'Adapter '.$adapter->getQuerySourceDisplayName().' is not capable of transactions'
            );
        }
        */
        
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        $adapters = [];
        
        foreach($this->_adapters as $adapter) {
            $adapters[] = $adapter->getQuerySourceDisplayName();
        }
        
        return [
            'level' => $this->_level,
            'adapters' => $adapters
        ];
    }
}
