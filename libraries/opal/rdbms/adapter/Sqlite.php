<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\adapter;

use df;
use df\core;
use df\opal;

class Sqlite extends Base_Pdo {
    
// Connection
    protected function _connect() {
        parent::_connect();
        
        $this->_connection->setAttribute(\PDO::ATTR_TIMEOUT, 60);
        
        if(version_compare($this->getServerVersion(), '3.6.19', '>=')) {
            $this->executeSql('PRAGMA foreign_keys = ON');
            $this->_support[self::FOREIGN_KEYS] = (bool)$this->executeSql('PRAGMA foreign_keys')->fetchColumn(0);
        } else {
            $this->_support[self::FOREIGN_KEYS] = false;
        }
    }

    protected function _getPdoDsn() {
        return 'sqlite:'.$this->_dsn->getDatabase();
    }
    
    protected function _getPdoOptions() {
        return array();
    }
    
    public function getServerType() {
        return 'sqlite';
    }
    
    public function getServerVersion() {
        return $this->_connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }
    
    protected function _supports($feature) {
        switch($feature) {
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
                
                foreach($res->fetchAll() as $row) {
                    if($row['compile_option'] == 'ENABLE_UPDATE_DELETE_LIMIT') {
                        return true;
                    }
                }
                
                return false;
            
            default:
                return false;
        }
    }
    
    public function _getConnectionException($number, $message) {
        return opal\rdbms\variant\sqlite\Server::getConnectionException($this, $number, $message);
    }
    
    public function _getQueryException($number, $message, $sql=null) {
        return opal\rdbms\variant\sqlite\Server::getQueryException($this, $number, $message, $sql);
    }
    
// Locks
    public function lockTable($table) {
        return false;
    }
    
    public function unlockTable($table) {
        return false;
    }
    
    
// Sanitize
    public function quoteIdentifier($identifier) {
        $parts = explode('.', $identifier);
        
        foreach($parts as $key => $part) {
            $parts[$key] = '`'.trim($part, '`\'').'`';
        }
        
        return implode('.', $parts);
    }
    
    public function quoteValue($value) {
        return $this->_connection->quote($value);
    }
    
    
    
    
// Introspection
    public function newSchema($name) {
        return new opal\rdbms\variant\sqlite\Schema($this, $name);
    }
}
