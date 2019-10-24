<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

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
        Glitch::incomplete($name);
    }

    public function getDatabaseList()
    {
        return [$this->_adapter->getDsn()->getDatabase()];
    }

    public function databaseExists($name)
    {
        return is_file($name);
    }

    public function createDatabase($name, $checkExists=false)
    {
        // stub
    }

    public function renameDatabase($oldName, $newName)
    {
        Glitch::incomplete($oldName, $newName);
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

        return Glitch::{'df/opal/rdbms/ETransaction'}($message, [
            'code' => $number,
            'data' => [
                'sql' => $sql
            ]
        ]);
    }

    private static function _getExceptionForError(opal\rdbms\IAdapter $adapter, $number, $message, $sql=null)
    {
        switch ($number) {
        // Query error
            case 1:
                if (preg_match('/no such table\: ([a-zA-Z0-9_]+)/i', $message, $matches)) {
                    return Glitch::{'df/opal/rdbms/ETableNotFound,ENotFound'}($message, [
                        'code' => $number,
                        'data' => [
                            'sql' => $sql,
                            'database' => null,
                            'table' => $matches[1]
                        ]
                    ]);
                }

                // no break
            case 13:
            case 18:
            case 20:
            case 25:
                return Glitch::{'df/opal/rdbms/ETransaction'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

        // Server error
            case 2:
            case 4:
            case 7:
            case 9:
            case 10:
            case 11:
            case 12:
            case 15:
            case 17:
            case 21:
            case 24:
            case 26:

        // Server unavailable
            case 5:
            case 6:
                return Glitch::{'df/opal/rdbms/EConnection'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

        // Permissions
            case 3:
            case 8:
            case 23:
                return Glitch::{'df/opal/rdbms/EAccess,EForbidden'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

        // DB not found
            case 14:
            case 16:
                return Glitch::{'df/opal/rdbms/EDatabaseNotFound,ENotFound'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

        // Constraint conflict
            case 19:
                return Glitch::{'df/opal/rdbms/EConstraint'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);

        // Feature support
            case 22:
                return Glitch::{'df/opal/rdbms/EFeatureSupport'}($message, [
                    'code' => $number,
                    'data' => [
                        'sql' => $sql
                    ]
                ]);
        }
    }
}
