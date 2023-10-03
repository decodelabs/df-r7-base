<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\variant\mysql;

use DecodeLabs\Exceptional;

use df\opal;

class Server implements opal\rdbms\IServer
{
    protected $_adapter;

    public function __construct(opal\rdbms\IAdapter $adapter)
    {
        $this->_adapter = $adapter;
    }

    public function getDatabase($name)
    {
        return opal\rdbms\Database::factory($this->_adapter, $name);
    }

    public function getDatabaseList()
    {
        $stmt = $this->_adapter->prepare('SHOW DATABASES');
        $res = $stmt->executeRead();
        $key = 'Database';
        $output = [];

        foreach ($res as $row) {
            $output[] = $row[$key];
        }

        return $output;
    }

    public function databaseExists($name)
    {
        $stmt = $this->_adapter->prepare('SHOW DATABASES LIKE :name');
        $stmt->bind('name', $name);
        $res = $stmt->executeRead();

        return !$res->isEmpty();
    }

    public function createDatabase($name, $checkExists = false)
    {
        $sql = 'CREATE DATABASE';

        if (!$checkExists) {
            $sql .= ' IF NOT EXISTS';
        }

        $sql .= ' ' . $this->_adapter->quoteIdentifier($name) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        $this->_adapter->executeSql($sql);
        return opal\rdbms\Database::factory($this->_adapter, $name);
    }

    public function renameDatabase($oldName, $newName)
    {
        $database = opal\rdbms\Database::factory($this->_adapter, $oldName);
        return $database->rename($newName);
    }




    // Exceptions
    public static function getConnectionException(opal\rdbms\IAdapter $adapter, $number, $message)
    {
        if ($e = self::_getExceptionForError($adapter, $number, $message)) {
            return $e;
        }

        return Exceptional::{'df/opal/rdbms/Connection'}($message, [
            'code' => $number
        ]);
    }

    public static function getQueryException(opal\rdbms\IAdapter $adapter, $number, $message, $sql = null)
    {
        if ($e = self::_getExceptionForError($adapter, $number, $message, $sql)) {
            return $e;
        }

        return Exceptional::{'df/opal/rdbms/Query'}($message, [
            'code' => $number,
            'data' => [
                'sql' => $sql
            ]
        ]);
    }

    private static function _getExceptionForError(opal\rdbms\IAdapter $adapter, $number, $message, $sql = null)
    {
        switch ($number) {
            // DB not found
            case 1049:
                return Exceptional::{'df/opal/rdbms/DatabaseNotFound,NotFound'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql

                    ]
                ]);

                // Table already exists
            case 1050:
                $database = $table = null;

                if (preg_match('/Table ([a-zA-Z0-9_]+)/', (string)$message, $matches)) {
                    $table = $matches[1];
                    $database = $adapter->getDsn()->getDatabase();
                }

                return Exceptional::{'df/opal/rdbms/TableConflict'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql,
                        'database' => $database,
                        'table' => $table
                    ]
                ]);

                // Table not found
            case 1051:
            case 1146:
                $database = $table = null;

                if (preg_match('/Table \'([^\s\.]+)\.([^\s\.]+)\'/', (string)$message, $matches)) {
                    $database = $matches[1];
                    $table = $matches[2];
                }

                return Exceptional::{'df/opal/rdbms/TableNotFound,NotFound'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql,
                        'database' => $database,
                        'table' => $table
                    ]
                ]);

                // Field not found
            case 1054:
                return Exceptional::{'df/opal/rdbms/FieldNotFound,NotFound'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

                // Trigger already exists
            case 1359:
                return Exceptional::{'df/opal/rdbms/TriggerConflict'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

                // Trigger not found
            case 1360:
                return Exceptional::{'df/opal/rdbms/TriggerNotFound,NotFound'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

                // Invalid trigger
            case 1336:

                // Constraint conflict
            case 1062:
            case 1451:
                return Exceptional::{'df/opal/rdbms/Constraint'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);


                // Permissions
            case 1044:
            case 1045:
            case 1095:
            case 1131:

                // Incorrect details
            case 1102:
            case 1103:
            case 1109:
            case 2047:
                return Exceptional::{'df/opal/rdbms/Access,Forbidden'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

                // Server unavailable
            case 1053:
            case 1077:
            case 1078:
            case 1079:
            case 1080:
            case 2006:
            case 2013:
            case 2055:

                // Socket
            case 1081:
            case 2001:
            case 2002:
            case 2004:
            case 2011:

                // Host
            case 1129:
            case 1130:
            case 2009:
            case 2010:
            case 2003:
            case 2005:
                return Exceptional::{'df/opal/rdbms/Connection'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);
        }

        return null;
    }
}
