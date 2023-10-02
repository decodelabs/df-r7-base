<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\adapter;

use DecodeLabs\Exceptional;

use df\opal;

class Mysqli extends opal\rdbms\adapter\Base
{
    // Connection
    protected function _connect($global = false)
    {
        if ($this->_connection) {
            return;
        }

        if (!extension_loaded('mysqli')) {
            throw Exceptional::{'df/opal/rdbms/AdapterNotFound,NotFound'}(
                'Mysqli extension is not available'
            );
        }

        try {
            $database = $global ? null : $this->_dsn->getDatabase();

            $connection = mysqli_connect(
                $this->_dsn->getHostName(),
                $this->_dsn->getUserName(),
                $this->_dsn->getPassword(),
                $database,
                $this->_dsn->getPort(),
                $this->_dsn->getSocket()
            );
        } catch (\Throwable $e) {
            $connection = null;
        }

        if ($num = mysqli_connect_errno()) {
            $this->_closeConnection();
            throw opal\rdbms\variant\mysql\Server::getConnectionException($this, $num, mysqli_connect_error());
        } elseif (!$connection) {
            throw opal\rdbms\variant\mysql\Server::getConnectionException($this, 0, 'Unable to connect');
        }

        $this->_connection = $connection;

        if (version_compare($this->getServerVersion(), '5.0.0', '<')) {
            $this->_closeConnection();

            throw Exceptional::{'df/opal/rdbms/AdapterNotFound,NotFound'}(
                'Opal only supports Mysql version 5 and above'
            );
        }

        if (!($charset = $this->_dsn->getOption('encoding'))) {
            $charset = 'utf8';
        }

        $charset = strtolower($charset);

        if ($charset === 'utf8') {
            mysqli_query($this->_connection, ' SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        }

        $this->executeSql('SET time_zone = \'+00:00\'');
    }

    protected function _createDb()
    {
        $this->executeSql('CREATE DATABASE `' . $this->_dsn->getDatabase() . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    protected function _closeConnection()
    {
        if ($this->_connection) {
            $output = mysqli_close($this->_connection);
        } else {
            $output = true;
        }

        $this->_connection = null;
        return $output;
    }

    public function getServerType()
    {
        return 'mysql';
    }

    public function getServerVersion()
    {
        if ($this->_connection) {
            return $this->_connection->server_info;
        }

        return null;
    }

    protected function _supports($feature)
    {
        switch ($feature) {
            case self::AUTO_INCREMENT:
                return true;

            case self::SEQUENCES:
                return false;

            case self::STORED_PROCEDURES:
                return version_compare($this->getServerVersion(), '5.1.0', '>=');

            case self::VIEWS:
                return version_compare($this->getServerVersion(), '5.0.1', '>=');

            case self::NESTED_TRANSACTIONS:
                return false;

            case self::TRIGGERS:
                return version_compare($this->getServerVersion(), '5.0.2', '>=');

            case self::FOREIGN_KEYS:
                return true;

            case self::UPDATE_LIMIT:
            case self::DELETE_LIMIT:
                return true;

            default:
                return false;
        }
    }


    // Transactions
    protected function _beginTransaction()
    {
        if (!mysqli_autocommit($this->_connection, false)) {
            throw Exceptional::{'df/opal/rdbms/Transaction'}(
                'Unable to begin transaction - ' . mysqli_error($this->_connection)
            );
        }
    }

    protected function _commitTransaction()
    {
        if (!mysqli_commit($this->_connection)) {
            throw Exceptional::{'df/opal/rdbms/Transaction'}(
                'Unable to commit transaction - ' . mysqli_error($this->_connection)
            );
        }

        mysqli_autocommit($this->_connection, true);
    }

    protected function _rollbackTransaction()
    {
        if (!mysqli_rollback($this->_connection)) {
            throw Exceptional::{'df/opal/rdbms/Transaction'}(
                'Unable to roll back transaction - ' . mysqli_error($this->_connection)
            );
        }

        mysqli_autocommit($this->_connection, true);
    }

    // Locks
    public function lockTable($table)
    {
        try {
            $this->executeSql('LOCK TABLE ' . $table . ' WRITE');
        } catch (opal\rdbms\Exception $e) {
            return false;
        }

        return true;
    }

    public function unlockTable($table)
    {
        try {
            $this->executeSql('UNLOCK TABLES');
        } catch (opal\rdbms\Exception $e) {
            return false;
        }

        return true;
    }


    // Query
    public function prepare($sql)
    {
        return new opal\rdbms\adapter\statement\Mysqli($this, $sql);
    }

    public function executeSql($sql, $forWrite = false)
    {
        $output = mysqli_query($this->_connection, $sql);

        if ($num = mysqli_errno($this->_connection)) {
            throw opal\rdbms\variant\mysql\Server::getQueryException($this, $num, mysqli_error($this->_connection), $sql);
        }

        return $output;
    }

    public function getLastInsertId()
    {
        $id = mysqli_insert_id($this->_connection);

        if ($id < 0) {
            $id = null;
            $result = mysqli_query($this->_connection, 'SELECT LAST_INSERT_ID()');

            if ($result instanceof \mysqli_result) {
                $row = mysqli_fetch_row($result);

                if (isset($row[0])) {
                    $id = $row[0];
                }
            }
        }

        return $id;
    }

    public function countAffectedRows()
    {
        return mysqli_affected_rows($this->_connection);
    }


    // Sanitize
    public function quoteIdentifier($identifier)
    {
        $parts = explode('.', $identifier);

        foreach ($parts as $key => $part) {
            $parts[$key] = '`' . trim((string)$part, '`\'') . '`';
        }

        return implode('.', $parts);
    }

    public function quoteFieldAliasDefinition($alias)
    {
        return '"' . trim((string)$alias, '`\'') . '"';
    }

    public function quoteFieldAliasReference($alias)
    {
        return '`' . trim((string)$alias, '`\'') . '`';
    }

    public function quoteValue($value)
    {
        return '\'' . mysqli_real_escape_string($this->_connection, $value) . '\'';
    }


    // Introspection
    public function newSchema($name)
    {
        return new opal\rdbms\variant\mysql\Schema($this, $name);
    }

    public function getServer()
    {
        return new opal\rdbms\variant\mysql\Server($this);
    }
}
