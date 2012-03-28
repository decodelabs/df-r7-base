<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\adapter\statement;

use df;
use df\core;
use df\opal;

class Pdo extends Base {
    
    protected $_stmt;
    
// Execute
    protected function _execute($forWrite=false) {
        try {
            $this->_stmt = $this->_adapter->getConnection()->prepare($this->_sql);
        } catch(\PDOException $e) {
            throw $this->_adapter->_getQueryException($e->errorInfo[1], $e->getMessage(), $this->_sql);
        }
        
        foreach($this->_bindings as $key => $value) {
            $this->_stmt->bindValue(':'.$key, $value);
        }
        
        try {
            $this->_stmt->execute();
        } catch(\PDOException $e) {
            throw $this->_adapter->_getQueryException($e->errorInfo[1], $e->getMessage(), $this->_sql);
        }
        
        return $this->_stmt;
    }
    
// Result
    protected function _fetchRow() {
        return $this->_stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function free() {
        $this->_stmt = null;
        return $this;
    }
}
