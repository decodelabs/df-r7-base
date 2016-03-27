<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\job;

use df;
use df\core;
use df\mesh;

class Transaction implements ITransaction {

    protected $_adapters = [];
    protected $_isOpen = true;

    public function isOpen() {
        return $this->_isOpen;
    }

    public function registerAdapter(ITransactionAdapter $adapter) {
        $id = $adapter->getTransactionId();

        if(!isset($this->_adapters[$id])) {
            $this->_adapters[$id] = $adapter;

            if($this->_isOpen) {
                $adapter->begin();
            }
        }

        return $this;
    }

    public function begin() {
        if(!$this->_isOpen) {
            foreach($this->_adapters as $adapter) {
                $adapter->begin();
            }

            $this->_isOpen = true;
        }

        return $this;
    }

    public function commit() {
        if($this->_isOpen) {
            foreach($this->_adapters as $adapter) {
                $adapter->commit();
            }

            $this->_isOpen = false;
        }

        return $this;
    }

    public function rollback() {
        if($this->_isOpen) {
            foreach($this->_adapters as $adapter) {
                $adapter->rollback();
            }

            $this->_isOpen = false;
        }

        return $this;
    }
}