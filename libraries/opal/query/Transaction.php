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
    protected $_application;
    protected $_adapters = array();
    
    public function __construct(core\IApplication $application) {
        $this->_application = $application;
    }
    
    public function select($field1=null) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginSelect(func_get_args());
    }
    
    public function fetch() {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginFetch();
    }
    
    public function insert($row) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginInsert($row);
    }
    
    public function batchInsert($rows=array()) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginBatchInsert($rows);
    }
    
    public function replace($row) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginReplace($row);
    }
    
    public function batchReplace($rows=array()) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginBatchReplace($rows);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginUpdate($valueMap);
    }
    
    public function delete() {
        return Initiator::factory($this->_application)
            ->setTransaction($this)
            ->beginDelete();
    }
    
    public function begin() {
        return new self($this->_application);
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
        
        if(!$isCapable && $forWrite) {
            throw new RuntimeException(
                'Adapter '.$adapter->getQuerySourceDisplayName().' is not capable of transactions'
            );
        }
        
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        $adapters = array();
        
        foreach($this->_adapters as $adapter) {
            $adapters[] = $adapter->getQuerySourceDisplayName();
        }
        
        return array(
            'level' => $this->_level,
            'adapters' => $adapters,
            'policyManager' => $this->_application
        );
    }
}
