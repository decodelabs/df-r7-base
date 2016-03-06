<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\adapter\statement;

use df;
use df\core;
use df\opal;
use df\flex;

abstract class Base implements opal\rdbms\IStatement, \IteratorAggregate, core\IDumpable {

    use core\collection\TExtractList;

    protected static $_queryCount = 0;

    protected $_sql;
    protected $_bindings = [];
    protected $_isExecuted = false;
    protected $_isUnbuffered = false;

    protected $_row;
    protected $_isEmpty = true;

    protected $_adapter;

    private $_keyIndex = 0;

    public static function getQueryCount() {
        return self::$_queryCount;
    }

    public function __construct(opal\rdbms\IAdapter $adapter, $sql=null) {
        $this->_adapter = $adapter;
        $this->setSql($sql);
    }

    public function getAdapter() {
        return $this->_adapter;
    }

    public function reset() {
        $this->_bindings = [];
        $this->_isExecuted = false;
        $this->_row = null;
        $this->_isEmpty = true;
        $this->_keyIndex = 0;
        return $this;
    }

    public function isUnbuffered($flag=null) {
        if($flag !== null) {
            $this->_isUnbuffered = (bool)$flag;
            return $this;
        }

        return $this->_isUnbuffered;
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

        return flex\Text::numericToAlpha($this->_keyIndex++);
    }

    public function setKeyIndex($index) {
        $this->_keyIndex = (int)$index;
        return $this;
    }

    public function getKeyIndex() {
        return $this->_keyIndex;
    }

    public function autoBind($value) {
        foreach($this->_bindings as $key => $testValue) {
            if($value === $testValue) {
                return $key;
            }
        }

        $key = $this->generateUniqueKey();
        $this->bind($key, $value);
        return $key;
    }

    public function bind($key, $value) {
        if($this->_isExecuted) {
            throw new opal\rdbms\RuntimeException(
                'Cannot change statement parameters, statement has already been executed'
            );
        }

        if(is_bool($value)) {
            $value = (int)$value;
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
            self::$_queryCount++;

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
            self::$_queryCount++;

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
            self::$_queryCount++;

            //core\debug()->dump($this);//, $this->_bindings);
        } catch(\Exception $e) {
            // void profiler
            throw $e;
        }

        // stop profiler
        return $this->_countAffectedRows();
    }

    abstract protected function _execute($forWrite=false);
    abstract protected function _countAffectedRows();


// Result
    public function import(...$values) {
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
        $output = [];

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