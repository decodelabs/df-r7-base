<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\adapter;

use DecodeLabs\Atlas;

use DecodeLabs\Genesis;
use df\opal;

class Sqlite extends Base_Pdo
{
    // Connection
    protected function _connect($global = false)
    {
        parent::_connect($global);

        $this->_connection->setAttribute(\PDO::ATTR_TIMEOUT, 60);

        if (version_compare($this->getServerVersion(), '3.6.19', '>=')) {
            $this->executeSql('PRAGMA foreign_keys = ON');
            $this->_support[self::FOREIGN_KEYS] = (bool)$this->executeSql('PRAGMA foreign_keys')->fetchColumn(0);
        } else {
            $this->_support[self::FOREIGN_KEYS] = false;
        }
    }

    protected function _createDb()
    {
        // don't need to do anything :)
    }

    protected function _getPdoDsn($global = false)
    {
        $database = $this->_dsn->getDatabaseKeyName();

        if (!$database || $database == 'default') {
            $database = Genesis::$hub->getSharedDataPath() . '/sqlite/default';

            if ($suffix = $this->_dsn->getDatabaseSuffix()) {
                $database .= $suffix;
            }

            $database .= '.db';
            Atlas::createDir(dirname($database));
        }

        return 'sqlite:' . $database;
    }

    protected function _getPdoOptions()
    {
        return [];
    }

    public function getServerType()
    {
        return 'sqlite';
    }

    public function getServerVersion()
    {
        return $this->_connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    protected function _supports($feature)
    {
        switch ($feature) {
            case self::AUTO_INCREMENT:
                return true;

            case self::SEQUENCES:
                return false;

            case self::STORED_PROCEDURES:
                return false;

            case self::VIEWS:
                return true;

            case self::NESTED_TRANSACTIONS:
                return false;

            case self::TRIGGERS:
                return version_compare($this->getServerVersion(), '2.5', '>=');

            case self::FOREIGN_KEYS:
                return version_compare($this->getServerVersion(), '3.6.19', '>=');

            case self::UPDATE_LIMIT:
            case self::DELETE_LIMIT:
                $res = $this->executeSql('PRAGMA compile_options');

                foreach ($res->fetchAll() as $row) {
                    if ($row['compile_option'] == 'ENABLE_UPDATE_DELETE_LIMIT') {
                        return true;
                    }
                }

                return false;

            default:
                return false;
        }
    }

    public function _getConnectionException($number, $message)
    {
        return opal\rdbms\variant\sqlite\Server::getConnectionException($this, $number, $message);
    }

    public function _getQueryException($number, $message, $sql = null)
    {
        return opal\rdbms\variant\sqlite\Server::getQueryException($this, $number, $message, $sql);
    }

    // Locks
    public function lockTable($table)
    {
        return false;
    }

    public function unlockTable($table)
    {
        return false;
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

    public function quoteFieldAliasReference($alias)
    {
        return '`' . trim((string)$alias, '`\'') . '`';
    }

    public function quoteValue($value)
    {
        return $this->_connection->quote($value);
    }




    // Introspection
    public function newSchema($name)
    {
        return new opal\rdbms\variant\sqlite\Schema($this, $name);
    }

    public function getServer()
    {
        return new opal\rdbms\variant\sqlite\Server($this);
    }
}
