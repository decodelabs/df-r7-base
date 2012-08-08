<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\adapter\statement;

use df;
use df\core;
use df\opal;

abstract class Base implements opal\rdbms\IStatement, \IteratorAggregate, core\IDumpable {
    
    use core\collection\TExtractList;
    
    protected $_sql;
    protected $_bindings = array();
    protected $_isExecuted = false;
    
    protected $_row;
    protected $_isEmpty = true;
    
    protected $_adapter;
    
    private $_keyIndex = 0;
    
    public function __construct(opal\rdbms\IAdapter $adapter, $sql=null) {
        $this->_adapter = $adapter;
        $this->setSql($sql);
    }
    
    public function getAdapter() {
        return $this->_adapter;
    }
    
    
// Preparation
    public function setSql($sql) {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Cannot change statement parameters, statement has already been executed'
            );
        }
        
        $this->_sql = $sql;
        return $this;
    }
    
    public function prependSql($sql) {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Cannot change statement parameters, statement has already been executed'
            );
        }
        
        $this->_sql = $sql.$this->_sql;
        return $this;
    }
    
    public function appendSql($sql) {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Cannot change statement parameters, statement has already been executed'
            );
        }
        
        $this->_sql .= $sql;
        return $this;
    }
    
    public function getSql() {
        return $this->_sql;
    }
    
    public function generateUniqueKey() {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Cannot change statement parameters, statement has already been executed'
            );
        }
        
        return core\string\Manipulator::numericToAlpha($this->_keyIndex++);
    }
    
    public function setKeyIndex($index) {
        $this->_keyIndex = (int)$index;
        return $this;
    }
    
    public function getKeyIndex() {
        return $this->_keyIndex;
    }
    
    public function bind($key, $value) {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Cannot change statement parameters, statement has already been executed'
            );
        }
        
        $this->_bindings[ltrim($key, ':')] = $value;
        return $this;
    }
    
    public function bindLob($key, $value) {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Cannot change statement parameters, statement has already been executed'
            );
        }
        
        core\stub($key, $value);
    }
    
    public function getBindings() {
        return $this->_bindings;
    }
    
    public function importBindings(opal\rdbms\IStatement $stmt) {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Cannot change statement parameters, statement has already been executed'
            );
        }
        
        $this->_bindings = array_merge($this->_bindings, $stmt->getBindings());
        return $this;
    }
    
    
// Execute
    public function executeRaw() {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Statements cannot be executed more than once'
            );
        }
        
        $this->_isExecuted = true;
        
        // begin profiler
        
        try {
            //$timer = new core\time\Timer();
            $result = $this->_execute();
            
            //core\debug()->dump($this->_sql, $timer);//, $this->_bindings);
        } catch(\Exception $e) {
            // void profiler
            throw $e;
        }
        
        // stop profiler
        return $result;
    }
    
    public function executeRead() {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Statements cannot be executed more than once'
            );
        }
        
        $this->_isExecuted = true;

        // begin profiler
        
        try {
            //$timer = new core\time\Timer();
            $this->_execute();
            
            //core\debug()->dump($this);//, /*$timer);//,*/ $this->_bindings);
        } catch(\Exception $e) {
            // void profiler
            throw $e;
        }
        
        // stop profiler
        
        if(!$this->_row = $this->_fetchRow()) {
            $this->free();
            $this->_isEmpty = true;
            $this->_row = null;
        } else {
            $this->_isEmpty = false;
        }
        
        return $this;
    }
    
    public function executeWrite() {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Statements cannot be executed more than once'
            );
        }
        
        $this->_isExecuted = true;
        
        // begin profiler
        
        try {
            //$timer = new core\time\Timer();
            $result = $this->_execute(true);
            
            //core\debug()->dump($this->_sql);//, $this->_bindings);
        } catch(\Exception $e) {
            // void profiler
            throw $e;
        }
        
        // stop profiler
        return $this->_adapter->countAffectedRows();
    }
    
    abstract protected function _execute($forWrite=false);
    
    
// Result
    public function import($value) {
        throw new core\collection\RuntimeException('This collection is read only');
    }
    
    public function isEmpty() {
        return $this->_isEmpty;
    }
    
    public function clear() {
        throw new core\collection\RuntimeException('This collection is read only');
    }
    
    public function extract() {
        if(!$this->_isExecuted) {
            $this->executeRead();
        }
        
        if($this->_isEmpty) {
            return $this->_row = null;
        }
        
        $output = $this->_row;
        $this->_row = $this->_fetchRow();
        
        if(!$this->_row) {
            $this->free();
            $this->_isEmpty = true;
            $this->_row = null;
        }
        
        return $output;
    }
    
    public function getCurrent() {
        return $this->_row;
    }
    
    public function toArray() {
        $output = array();
        
        foreach($this as $key => $value) {
            $output[$key] = $value;
        }
        
        return $output;
    }
    
    public function count() {
        throw new core\collection\RuntimeException('This collection is streamed and cannot be counted');
    }
    
    public function getIterator() {
        if(!$this->_isExecuted) {
            $this->executeRead();
        }
        
        return new core\collection\ReductiveIndexIterator($this);
    }
    
    abstract protected function _fetchRow();
    
// Dump
    public function getDumpProperties() {
        $output = [
            'sql' => $this->_sql,
            'bindings' => $this->_bindings
        ];

        if($this->_isExecuted) {
            $output['current'] = $this->getCurrent();
        }

        return $output;
    }
} 