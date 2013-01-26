<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\adapter;

use df;
use df\core;
use df\opal;

abstract class Base implements opal\rdbms\IAdapter, core\IDumpable {
    
    const AUTO_INCREMENT = 1;
    const SEQUENCES = 2;
    const STORED_PROCEDURES = 3;
    const VIEWS = 4;
    const NESTED_TRANSACTIONS = 5;
    const TRIGGERS = 6;
    const FOREIGN_KEYS = 7; 
    const UPDATE_LIMIT = 8;
    const DELETE_LIMIT = 9;
    
    private static $_connections = array();
    
    protected $_dsn;
    protected $_connection;
    protected $_transactionLevel = 0;
    protected $_support = array();
    
    public static function factory($dsn) {
        $dsn = opal\rdbms\Dsn::factory($dsn);
        $hash = $dsn->getHash();
        
        if(isset(self::$_connections[$hash])) {
            if(self::$_connections[$hash]->isConnected()) {
                return self::$_connections[$hash];
            }
            
            unset(self::$_connections[$hash]);
        }
        
        $adapterName = ucfirst($dsn->getAdapter());
        $class = 'df\\opal\\rdbms\\adapter\\'.$adapterName;
        
        if(!class_exists($class)) {
            throw new opal\rdbms\AdapterNotFoundException(
                'RDBMS adapter '.$adapterName.' could not be found'
            );
        }
        
        self::$_connections[$hash] = new $class($dsn);
        return self::$_connections[$hash];
    }
    
    public static function destroyConnection($hash) {
        if($hash instanceof opal\rdbms\IDsn) {
            $hash = $dsn->getHash();
        }
        
        if(isset(self::$_connections[$hash])) {
            unset(self::$_connections[$hash]);
        }
    }
    
    
    protected function __construct(opal\rdbms\IDsn $dsn) {
        $this->_dsn = $dsn;
        $this->_connect();
    }
    
    public function __destruct() {
        $this->closeConnection();
    }

    public function getAdapterName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
    
    
// Connection
    public function isConnected() {
        return $this->_connection !== null;
    }
    
    public function closeConnection() {
        $this->_closeConnection();
        
        if($this->_dsn) {
            self::destroyConnection($this->_dsn->getHash());
        }
        
        return $this;
    }
    
    public function getConnection() {
        return $this->_connection;
    }
    
    public function getDsn() {
        return $this->_dsn;
    }
    
    public function getDsnHash() {
        return $this->_dsn->getHash();
    }
    
    public function supports($feature) {
        if(!isset($this->_support[$feature])) {
            $this->_support[$feature] = (bool)$this->_supports($feature);
        }
        
        return $this->_support[$feature];
    }
    
    
// Transaction
    public function begin() {
        if($this->_transactionLevel === 0 || $this->supports(self::NESTED_TRANSACTIONS)) {
            $this->_beginTransaction();
        }
        
        $this->_transactionLevel++;
        return $this;
    }
    
    public function commit() {
        if($this->_transactionLevel > 0) {
            if($this->_transactionLevel === 1 || $this->supports(self::NESTED_TRANSACTIONS)) {
                $this->_commitTransaction();
            }
            
            $this->_transactionLevel--;
        }
        
        return $this;
    }
    
    public function rollback() {
        if($this->_transactionLevel > 0) {
            if($this->_transactionLevel === 1 || $this->supports(self::NESTED_TRANSACTIONS)) {
                $this->_rollbackTransaction();
            }
            
            $this->_transactionLevel--;
        }
        
        return $this;
    }
    
    public function isTransactionOpen() {
        return $this->_transactionLevel > 0;
    }
    
// Sanitize
    public function quoteTableAliasDefinition($alias) {
        return $this->quoteIdentifier($alias);
    }
    
    public function quoteTableAliasReference($alias) {
        return $this->quoteIdentifier($alias);
    }
    
    public function quoteFieldAliasReference($alias) {
        return $this->quoteIdentifier($alias);
    }

    public function quoteFieldAliasDefinition($alias) {
        return $this->quoteValue($alias);
    }

    public function prepareValue($value, opal\rdbms\schema\IField $field=null) {
        if($field !== null) {
            if(false !== ($preppedValue = $this->_prepareKnownValue($value, $field))) {
                return $preppedValue;
            }
            
            if($field instanceof opal\rdbms\schema\field\Int) {
                return (int)$value;
            }
        }

        if($value instanceof core\time\IDate) {
            $value = $this->_prepareDateValue($value);
        } else if(is_numeric($value)) {
            return $value;
        } else if($value === null) {
            return 'NULL';
        }
        
        return $this->quoteValue($value);
    }
    
    protected function _prepareKnownValue($value, opal\rdbms\schema\IField $field) {
        return false;
    }
    
    protected function _prepareDateValue(core\time\IDate $value) {
        return $value->toUtc()->format('Y-m-d H:i:s');
    }
    
    
// Introspection
    public function getDatabase() {
        return opal\rdbms\Database::factory($this);
    }

    public function getTable($name) {
        return opal\rdbms\Table::factory($this, $name);
    }
    
    public function createTable(opal\rdbms\schema\ISchema $schema, $dropIfExists=false) {
        $table = $this->getTable($schema->getName());
        $table->create($schema, $dropIfExists);
        
        return $table;
    }
    
    public function getSchema($name) {
        return $this->getTable($name)->getSchema();
    }
    
    
// Policy
    public function getEntityLocator() {
        return new core\policy\EntityLocator('opal://rdbms/'.$this->getAdapterName().':"'.$this->getDsn()->getConnectionString().'"');
    }

    public function fetchSubEntity(core\policy\IManager $manager, core\policy\IEntityLocatorNode $node) {
        $id = $node->getId();
        
        if($id === null) {
            throw new core\policy\EntityNotFoundException(
                'Opal entities must be referenced with an id in it\'s locator'
            );
        }
        
        switch($node->getType()) {
            case 'Table':
                return $this->getTable($id);
                
            case 'Schema':
                return $this->getTable($id)->getSchema();    
        }
    }
    
    
// Stubs
    abstract protected function _connect();
    abstract protected function _closeConnection();
    abstract protected function _supports($feature);
    abstract protected function _beginTransaction();
    abstract protected function _commitTransaction();
    abstract protected function _rollbackTransaction();
    
    
    
// Dump
    public function getDumpProperties() {
        return $this->_dsn->getDisplayString();
    }
}





/**
 * PDO
 */
abstract class Base_Pdo extends opal\rdbms\adapter\Base {
    
    protected $_affectedRows = 0;
    
// Connection
    protected function _connect() {
        if($this->_connection) {
            return;
        }
        
        if(!extension_loaded('pdo')) {
            throw new opal\rdbms\AdapterNotFoundException(
                'PDO is not currently available'
            );
        }
        
        $pdoType = strtolower($this->getServerType());
        
        if(!in_array($pdoType, \PDO::getAvailableDrivers())) {
            throw new opal\rdbms\AdapterNotFoundException(
                'PDO adapter '.$pdoType.' is not currently available'
            );
        }
        
        try {
            $connection = new \PDO(
                $this->_getPdoDsn(),
                $this->_dsn->getUsername(),
                $this->_dsn->getPassword(),
                $this->_getPdoOptions()
            );
            
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch(\PDOException $e) {
            throw $this->_getConnectionException($e->getCode(), $e->getMessage());
        }
        
        $this->_connection = $connection;
    }
    
    abstract protected function _getPdoDsn();
    abstract protected function _getPdoOptions();
    
    abstract public function _getConnectionException($code, $message);
    abstract public function _getQueryException($code, $message, $sql=null);
    
    protected function _closeConnection() {
        $this->_connection = null;
        return true;
    }
    
    
// Transactions
    protected function _beginTransaction() {
        $this->_connection->beginTransaction();
    }
    
    protected function _commitTransaction() {
        $this->_connection->commit();
    }
    
    protected function _rollbackTransaction() {
        $this->_connection->rollBack();
    }
    
    
    
// Query
    public function prepare($sql) {
        return new opal\rdbms\adapter\statement\Pdo($this, $sql);
    }
    
    public function executeSql($sql, $forWrite=false) {
        try {
            if($forWrite) {
                $this->_affectedRows = $this->_connection->exec($sql);
                
                if($this->_affectedRows === false) {
                    $this->_affectedRows = 0;
                    $info = $this->_connection->errorInfo();
                    throw $this->_getQueryException($info[1], $info[2], $sql);
                }
                
                return $this->_affectedRows;
            } else {
                return $this->_connection->query($sql);
            }
        } catch(\PDOException $e) {
            $info = $this->_connection->errorInfo();
            throw $this->_getQueryException($info[1], $info[2], $sql);
        }
    }
    
    public function getLastInsertId() {
        return $this->_connection->lastInsertId();
    }
    
    public function countAffectedRows() {
        return $this->_affectedRows;
    }
}
