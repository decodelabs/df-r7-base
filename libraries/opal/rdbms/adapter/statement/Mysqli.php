<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\adapter\statement;

use df\opal;

class Mysqli extends Base
{
    protected $_affectedRows = 0;
    protected $_result;


    // Execute
    protected function _execute($forWrite = false)
    {
        $this->_affectedRows = 0;
        $connection = $this->_adapter->getConnection();
        $stmt = $connection->stmt_init();

        $bindings = [];
        $sql = '';
        $length = strlen((string)$this->_sql);
        $mode = 0;
        $var = '';
        $quoteChar = null;

        for ($i = 0; $i < $length + 1; $i++) {
            if (isset($this->_sql[$i])) {
                $char = $this->_sql[$i];
            } else {
                $char = '';
            }

            switch ($mode) {
                case 0: // Root
                    if ($char == ':') {
                        $mode = 1;
                        break;
                    } elseif ($char == '\'' || $char == '`' || $char == '"') {
                        $quoteChar = $char;
                        $mode = 2;
                    }

                    $sql .= $char;
                    break;

                case 1: // Var
                    if (!ctype_alnum($char) && $char != '_') {
                        if (array_key_exists($var, $this->_bindings)) {
                            $sql .= '?';
                            $bindings[] = $this->_bindings[$var];
                        } else {
                            $sql .= ':' . $var;
                        }

                        $sql .= $char;
                        $var = '';
                        $mode = 0;
                    } else {
                        $var .= $char;
                    }

                    break;

                case 2: // Quote
                    if ($char == $quoteChar) {
                        $mode = 0;
                    }

                    $sql .= $char;
                    break;
            }
        }

        if (!$stmt->prepare($sql)) {
            throw opal\rdbms\variant\mysql\Server::getQueryException(
                $this->_adapter,
                mysqli_errno($connection),
                mysqli_error($connection),
                [$sql, $this->_bindings]
            );
        }

        if (!empty($bindings)) {
            $args = $this->_refBindings($bindings);
            $types = str_repeat('s', count($bindings));

            $stmt->bind_param($types, ...$args);
        }

        $stmt->execute();

        if ($num = mysqli_errno($connection)) {
            throw opal\rdbms\variant\mysql\Server::getQueryException(
                $this->_adapter,
                $num,
                mysqli_error($connection),
                [$sql, $this->_bindings]
            );
        }

        $this->_affectedRows = $stmt->affected_rows;
        return $this->_result = $stmt->get_result();
    }

    protected function _refBindings(array &$bindings)
    {
        $refs = [];

        foreach ($bindings as $key => $value) {
            $bindings[$key] = $this->_adapter->normalizeValue($value);
            $refs[$key] = &$bindings[$key];
        }

        return $refs;
    }


    // Result
    protected function _fetchRow()
    {
        if ($this->_result) {
            return $this->_result->fetch_assoc();
        }

        return null;
    }

    public function free()
    {
        if ($this->_result) {
            $this->_result->free();
            $this->_result = null;
        }

        return $this;
    }

    public function count(): int
    {
        return $this->_result->num_rows;
    }

    protected function _countAffectedRows()
    {
        return $this->_affectedRows;
    }
}
