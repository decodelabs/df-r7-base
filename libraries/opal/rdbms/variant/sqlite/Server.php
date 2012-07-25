<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

use df;
use df\core;
use df\opal;

class Server implements opal\rdbms\IServer {
    
    public static function getConnectionException(opal\rdbms\IAdapter $adapter, $number, $message) {
        if($e = self::_getExceptionForError($adapter, $number, $message)) {
            return $e;
        }
        
        return new opal\rdbms\ConnectionException($message, $number);
    }

    public static function getQueryException(opal\rdbms\IAdapter $adapter, $number, $message, $sql=null) {
        if($e = self::_getExceptionForError($adapter, $number, $message, $sql)) {
            return $e;
        }
        
        return new opal\rdbms\QueryException($message, $number, $sql);
    }
    
    private static function _getExceptionForError(opal\rdbms\IAdapter $adapter, $number, $message, $sql=null) {
        switch($number) {
        // Query error
            case 1:
            case 13:
            case 18:
            case 20:
            case 25:
                return new opal\rdbms\QueryException($message, $number, $sql);
               
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
                return new opal\rdbms\ConnectionException($message, $number, $sql);
                
        // Permissions
            case 3:
            case 8:
            case 23:
                return new opal\rdbms\AccessException($message, $number, $sql);
                
        // DB not found
            case 14:
            case 16:
                return new opal\rdbms\DatabaseNotFoundException($message, $number, $sql);
                
        // Constraint conflict
            case 19:
                return new opal\rdbms\ConstraintException($message, $number, $sql);
                
        // Feature support
            case 22:
                return new opal\rdbms\FeatureSupportException($message, $number, $sql);
        }
    }
}
