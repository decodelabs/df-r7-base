<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms;

use df;
use df\core;
use df\opal;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}

interface IAdapterException {}
class AdapterNotFoundException extends RuntimeException implements IAdapterException {}
class FeatureSupportException extends RuntimeException implements IAdapterException {}
class EngineSupportException extends FeatureSupportException {}
class AutoIncrementSupportException extends FeatureSupportException {}
class SequenceSupportException extends FeatureSupportException {}
class ForeignKeySupportException extends FeatureSupportException {}
class StoredProcedureSupportException extends FeatureSupportException {}
class ViewSupportException extends FeatureSupportException {}
class TriggerSupportException extends FeatureSupportException {}

class SQLError extends RuntimeException implements core\IDumpable {
    
    protected $_sql;
    
    public function __construct($message, $code=0, $sql=null) {
        parent::__construct($message, $code);
        $this->_sql = $sql;
    }
    
    public function getDumpProperties() {
        return array('SQL' => $this->_sql);
    }
}

class ConnectionException extends SQLError {}
class AccessException extends ConnectionException {}

class QueryException extends SQLError {}
class TransactionException extends QueryException {}

class DatabaseNotFoundException extends QueryException {}
class DatabaseConflictException extends QueryException {}

class TableNotFoundException extends QueryException {
    
    public $database;
    public $table;
    
    public function __construct($message, $code=0, $sql=null, $database=null, $table=null) {
        parent::__construct($message, $code, $sql);
        
        $this->database = $database;
        $this->table = $table;
    }
    
    public function getDumpProperties() {
        return array(
            'database' => $this->database,
            'table' => $this->table,
            'SQL' => $this->_sql
        );
    }
}

class TableConflictException extends QueryException {}

class FieldNotFoundException extends QueryException {}
class FieldConflictException extends QueryException {}

class IndexNotFoundException extends QueryException {}
class IndexConflictException extends QueryException {}

class ForeignKeyNotFoundException extends QueryException {}
class ForeignKeyConflictException extends QueryException {}

class TriggerNotFoundException extends QueryException {}
class TriggerConflictException extends QueryException {}

class ConstraintException extends QueryException {}


// Interfaces
interface IDsn extends core\IStringProvider {
    public function setAdapter($adapter);
    public function getAdapter();
    public function setUsername($userName);
    public function getUsername();
    public function setPassword($password);
    public function getPassword();
    public function setProtocol($protocol);
    public function getProtocol();
    public function setHostname($hostname);
    public function getHostname($default='localhost');
    public function setPort($port);
    public function getPort();
    public function setSocket($socket);
    public function getSocket();
    public function setDatabase($database);
    public function getDatabase();
    public function setOption($key, $value);
    public function getOption($key, $default=null);
    public function getHash();
    public function getConnectionString();
    public function getDisplayString();
}


interface IAdapter extends core\policy\IParentEntity {
// Connection
    public function isConnected();
    public function closeConnection();
    public function getConnection();
    public function getDsn();
    public function getDsnHash();
    public function getServerType();
    public function getServerVersion();
    public function supports($feature);
    
// Transaction
    public function begin();
    public function commit();
    public function rollback();
    public function isTransactionOpen();
    
// Lock
    public function lockTable($table);
    public function unlockTable($table);
    
// Query
    public function prepare($sql);
    public function executeSql($sql, $forWrite=false);
    public function getLastInsertId();
    public function countAffectedRows();
    
// Sanitize
    public function quoteIdentifier($identifier);
    public function quoteValue($value);
    public function quoteTableAliasDefinition($alias);
    public function quoteTableAliasReference($alias);
    public function quoteFieldAliasDefinition($alias);
    public function quoteFieldAliasReference($alias);
    public function prepareValue($value, opal\rdbms\schema\IField $field=null);
    
    
// Introspection
    public function getDatabase();
    public function getTable($name);
    public function createTable(opal\rdbms\schema\ISchema $schema, $dropIfExists=false);
    public function getSchema($name);
    public function newSchema($name);
}   


interface IStatement extends core\collection\IQueue, core\collection\IStreamCollection {
    public function getAdapter();
    public function setSql($sql);
    public function prependSql($sql);
    public function appendSql($sql);
    public function getSql();
    
    public function generateUniqueKey();
    public function setKeyIndex($index);
    public function getKeyIndex();
    
    public function bind($key, $value);
    public function bindLob($key, $value);
    public function getBindings();
    public function importBindings(IStatement $statement);
    
    public function executeRaw();
    public function executeRead();
    public function executeWrite();
    
    public function free();
}

interface IServer {
    public static function getConnectionException($number, $message);
    public static function getQueryException($number, $message, $sql=null);
}

interface IDatabase {
    public function getName();
    public function getAdapter();

    public function getTable($name);
    public function getTableList();

    //public function exists();
    //public function create();
    //public function rename();
    
    public function drop();
    public function truncate();
}

interface ITable extends core\policy\IEntity, opal\query\IAdapter, opal\query\IEntryPoint, \Countable, opal\schema\ISchemaContext {
    public function getName();
    public function getAdapter();
    public function getSchema($voidCache=false);
    
    public function exists();
    public function create(opal\rdbms\schema\ISchema $schema, $dropIfExists=false);
    public function alter(opal\rdbms\schema\ISchema $schema);
    public function rename($newName);
    public function drop();
    public function truncate();
    public function lock();
    public function unlock();
}