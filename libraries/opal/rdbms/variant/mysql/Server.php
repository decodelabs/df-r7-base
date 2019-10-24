<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\mysql;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch;

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

    public function createDatabase($name, $checkExists=false)
    {
        $encoding = $this->_adapter->getEncoding();
        $sql = 'CREATE DATABASE';

        if (!$checkExists) {
            $sql .= ' IF NOT EXISTS';
        }

        $sql .= ' '.$this->_adapter->quoteIdentifier($name).' CHARACTER SET '.$encoding.' COLLATE '.$encoding.'_general_ci';
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

        return Glitch::{'df/opal/rdbms/EConnection'}($message, [
            'code' => $number
        ]);
    }

    public static function getQueryException(opal\rdbms\IAdapter $adapter, $number, $message, $sql=null)
    {
        if ($e = self::_getExceptionForError($adapter, $number, $message, $sql)) {
            return $e;
        }

        return Glitch::{'df/opal/rdbms/EQuery'}($message, [
            'code' => $number,
            'data' => [
                'sql' => $sql
            ]
        ]);
    }

    private static function _getExceptionForError(opal\rdbms\IAdapter $adapter, $number, $message, $sql=null)
    {
        switch ($number) {

        // DB not found
            case 1049:
                return Glitch::{'df/opal/rdbms/EDatabaseNotFound,ENotFound'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql

                    ]
                ]);

        // Table already exists
            case 1050:
                $database = $table = null;

                if (preg_match('/Table ([a-zA-Z0-9_]+)/', $message, $matches)) {
                    $table = $matches[1];
                    $database = $adapter->getDsn()->getDatabase();
                }

                return Glitch::{'df/opal/rdbms/ETableConflict'}($message, [
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

                if (preg_match('/Table \'([^\s\.]+)\.([^\s\.]+)\'/', $message, $matches)) {
                    $database = $matches[1];
                    $table = $matches[2];
                }

                return Glitch::{'df/opal/rdbms/ETableNotFound,ENotFound'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql,
                        'database' => $database,
                        'table' => $table
                    ]
                ]);

        // Field not found
            case 1054:
                return Glitch::{'df/opal/rdbms/EFieldNotFound,ENotFound'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

        // Trigger already exists
            case 1359:
                return Glitch::{'df/opal/rdbms/ETriggerConflict'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

        // Trigger not found
            case 1360:
                return Glitch::{'df/opal/rdbms/ETriggerNotFound,ENotFound'}($message, [
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
                return Glitch::{'df/opal/rdbms/EConstraint'}($message, [
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
                return Glitch::{'df/opal/rdbms/EAccess,EForbidden'}($message, [
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
                return Glitch::{'df/opal/rdbms/EConnection'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);
        }

        return null;
    }
}
