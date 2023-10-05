<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\adapter\statement;

class Pdo extends Base
{
    protected $_stmt;
    protected $_cache;

    // Execute
    protected function _execute($forWrite = false)
    {
        $options = [];

        if ($this->_isUnbuffered) {
            $this->_adapter = clone $this->_adapter;
        }

        $connection = $this->_adapter->getConnection();

        if ($this->_isUnbuffered) {
            $connection->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        try {
            $this->_stmt = $connection->prepare($this->_sql, $options);
        } catch (\PDOException $e) {
            throw $this->_adapter->_getQueryException(
                $e->errorInfo[1],
                $e->getMessage(),
                [$this->_sql, $this->_bindings]
            );
        }

        foreach ($this->_bindings as $key => $value) {
            $this->_stmt->bindValue(':' . $key, $this->_adapter->normalizeValue($value));
        }

        try {
            $this->_stmt->execute();
        } catch (\PDOException $e) {
            throw $this->_adapter->_getQueryException(
                $e->errorInfo[1],
                $e->getMessage(),
                [$this->_sql, $this->_bindings]
            );
        }

        if ($this->_isUnbuffered) {
            $connection->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        return $this->_stmt;
    }

    // Result
    protected function _fetchRow()
    {
        if ($this->_cache !== null) {
            return array_shift($this->_cache);
        }

        return $this->_stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function free()
    {
        $this->_stmt = null;

        if ($this->_isUnbuffered && $this->_adapter->isClone()) {
            $this->_adapter->closeConnection();
        }

        return $this;
    }

    public function count(): int
    {
        if ($this->_cache === null) {
            $this->_cache = [];

            if ($this->_stmt) {
                while ($row = $this->_stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $this->_cache[] = $row;
                }
            }
        }

        $output = count($this->_cache);

        if ($this->_row) {
            $output++;
        }

        return $output;
    }

    protected function _countAffectedRows()
    {
        if (!$this->_stmt) {
            return 0;
        }

        return $this->_stmt->rowCount();
    }
}
