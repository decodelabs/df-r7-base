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
    protected $_source;

    public function __construct($source=false) {
        if($source === false) {
            $source = null;
        } else if(!$source) {
            throw new InvalidArgumentException(
                'Implicit source transaction has no source'
            );
        }

        $this->_source = $source;
    }
    
    public function select($field1=null) {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginSelect(func_get_args());

        if($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }

    public function selectDistinct($field1=null) {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginSelect(func_get_args(), true);

        if($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }

    public function countAll() {
        if($this->_source === null) {
            throw new RuntimeException(
                'Cannot countAll without implicit source'
            );
        }

        return $this->select()->count();
    }

    public function countAllDistinct() {
        if($this->_source === null) {
            throw new RuntimeException(
                'Cannot countAll without implicit source'
            );
        }

        return $this->selectDistinct()->count();
    }

    public function union() {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginUnion()
            ->with(func_get_args());

        if($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }
    
    public function fetch() {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginFetch();

        if($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }
    
    public function insert($row) {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginInsert($row);

        if($this->_source !== null) {
            $output = $output->into($this->_source);
        }

        return $output;
    }
    
    public function batchInsert($rows=[]) {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginBatchInsert($rows);

        if($this->_source !== null) {
            $output = $output->into($this->_source);
        }

        return $output;
    }
    
    public function replace($row) {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginReplace($row);

        if($this->_source !== null) {
            $output = $output->in($this->_source);
        }

        return $output;
    }
    
    public function batchReplace($rows=[]) {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginBatchReplace($rows);

        if($this->_source !== null) {
            $output = $output->in($this->_source);
        }

        return $output;
    }
    
    public function update(array $valueMap=null) {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginUpdate($valueMap);

        if($this->_source !== null) {
            $output = $output->in($this->_source);
        }

        return $output;
    }
    
    public function delete() {
        $output = Initiator::factory()
            ->setTransaction($this)
            ->beginDelete();

        if($this->_source !== null) {
            $output = $output->from($this->_source);
        }

        return $output;
    }
    
    public function begin() {
        if($this->_source !== null) {
            return new self($this->_source);
        } else {
            return new self();
        }
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
