<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\adapter;

use df;
use df\core;
use df\opal;

class Mysql extends Base_Pdo {
    
// Connection
    protected function _connect($global=false) {
        parent::_connect($global);
        
        if(version_compare($this->getServerVersion(), '5.0.0', '<')) {
            $this->_closeConnection();
            
            throw new opal\rdbms\AdapterNotFoundException(
                'Opal only supports Mysql version 5 and above'
            );
        }

        $this->executeSql('SET time_zone = \'+00:00\'');
    }

    protected function _createDb() {
        $encoding = $this->getEncoding();
        $this->executeSql('CREATE DATABASE `'.$this->_dsn->getDatabase().'` CHARACTER SET '.$encoding.' COLLATE '.$encoding.'_general_ci');
    }
    
    protected function _getPdoDsn($global=false) {
        if(!($charset = $this->_dsn->getOption('encoding'))) {
            $charset = 'utf8';
        }
        
        $output = 'mysql:host='.$this->_dsn->getHostname();

        if(!$global) {
            $output .= ';dbname='.$this->_dsn->getDatabase();
        }

        $output .= ';charset='.$charset;
        return $output;
    }
    
    protected function _getPdoOptions() {
        $output = [];
        
        if(!($charset = $this->_dsn->getOption('encoding'))) {
            $charset = 'utf8';
        }
        
        if(strtolower($charset) == 'utf8') {
            $output[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
        }
        
        return $output;
    }
    
    public function getServerType() {
        return 'mysql';
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
    
    public function _getConnectionException($number, $message) {
        return opal\rdbms\variant\mysql\Server::getConnectionException($this, $number, $message);
    }
    
    public function _getQueryException($number, $message, $sql=null) {
        return opal\rdbms\variant\mysql\Server::getQueryException($this, $number, $message, $sql);
    }
    
    
// Locks
    public function lockTable($table) {
        try {
            $this->executeSql('LOCK TABLE '.$table.' WRITE');
        } catch(opal\rdbms\IException $e) {
            return false;
        }
        
        return true;
    }
    
    public function unlockTable($table) {
        try {
            $this->executeSql('UNLOCK TABLES');
        } catch(opal\rdbms\IException $e) {
            return false;
        }
        
        return true;
    }
    
    
// Sanitize
    public function quoteIdentifier($identifier) {
        $parts = explode('.', $identifier);
        
        foreach($parts as $key => $part) {
            $parts[$key] = '`'.trim($part, '`\'').'`';
        }
        
        return implode('.', $parts);
    }

    public function quoteFieldAliasDefinition($alias) {
        return '"'.trim($alias, '`\'').'"';
    }

    public function quoteFieldAliasReference($alias) {
        return '`'.trim($alias, '`\'').'`';
    }
    
    public function quoteValue($value) {
        return $this->_connection->quote($value);
    }
    
    
// Introspection
    public function newSchema($name) {
        return new opal\rdbms\variant\mysql\Schema($this, $name);
    }

    public function getServer() {
        return new opal\rdbms\variant\mysql\Server($this);
    }
}