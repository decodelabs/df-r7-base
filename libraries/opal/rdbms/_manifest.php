<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms;

use df;
use df\core;
use df\opal;
use df\mesh;

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
        return ['SQL' => $this->_sql];
    }
}

class ConnectionException extends SQLError {}
class AccessException extends ConnectionException {}

class QueryException extends SQLError {}
class TransactionException extends QueryException {}

class DatabaseNotFoundException extends QueryException {}
class DatabaseConflictException extends QueryException {}

class TableQueryException extends QueryException {
    
    public $database;
    public $table;
    
    public function __construct($message, $code=0, $sql=null, $database=null, $table=null) {
        parent::__construct($message, $code, $sql);
        
        $this->database = $database;
        $this->table = $table;
    }
    
    public function getDumpProperties() {
        return [
            'database' => $this->database,
            'table' => $this->table,
            'SQL' => $this->_sql
        ];
    }
}

class TableNotFoundException extends TableQueryException {}
class TableConflictException extends TableQueryException {}

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
    public function setDatabaseKeyName($name);
    public function getDatabaseKeyName();
    public function setDatabaseSuffix($suffix);
    public function getDatabaseSuffix();
    public function setOption($key, $value);
    public function getOption($key, $default=null);
    public function getHash();
    public function getServerHash();
    public function getConnectionString();
    public function getServerString();
    public function getDisplayString($credentials=false);
}


interface IAdapter extends mesh\entity\IParentEntity {
    public function getAdapterName();

// Connection
    public function isConnected();
    public function isClone();
    public function closeConnection();
    public function getConnection();
    public function getDsn();
    public function getDsnHash();
    public function getServerDsnHash();
    public function switchDatabase($name);
    public function getServerType();
    public function getServerVersion();
    public function supports($feature);
    public function getEncoding();
    
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
    public function normalizeValue($value);
    
    
// Introspection
    public function getServer();
    public function getDatabaseList();
    public function databaseExists($name);

    public function getDatabase();
    public function tableExists($name);

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
    public function reset();
    public function isUnbuffered($flag=null);
    
    public function generateUniqueKey();
    public function setKeyIndex($index);
    public function getKeyIndex();
    
    public function autoBind($value);
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
    public static function getConnectionException(IAdapter $adapter, $number, $message);
    public static function getQueryException(IAdapter $adapter, $number, $message, $sql=null);

    public function getDatabase($name);
    public function getDatabaseList();
    public function databaseExists($name);
    public function createDatabase($name, $checkExists=false);
    public function renameDatabase($oldName, $newName);
}

interface IDatabase {
    public function getName();
    public function getAdapter();

    public function getTable($name);
    public function getTableList();

    public function drop();
    public function truncate();
    public function rename($newName, $overwrite=false);

    public function setCharacterSet($set, $collation=null);
    public function getCharacterSet();
    public function setCollation($collation);
    public function getCollation();
}

interface ITable extends mesh\entity\IEntity, opal\query\IAdapter, opal\query\IEntryPoint, \Countable, opal\schema\ISchemaContext {
    public function getName();
    public function getAdapter();
    public function getSchema();
    public function getStats();
    public function getDatabaseName();
    
    public function exists();
    public function create(opal\rdbms\schema\ISchema $schema, $dropIfExists=false);
    public function alter(opal\rdbms\schema\ISchema $schema);
    public function rename($newName);
    public function drop();
    public function truncate();
    public function lock();
    public function unlock();

    public function setCharacterSet($set, $collation=null, $convert=false);
    public function getCharacterSet();
    public function setCollation($collation, $convert=false);
    public function getCollation();
}

interface ITableStats extends core\IAttributeContainer {
    public function setVersion($version);
    public function getVersion();
    public function setRowCount($rows);
    public function getRowCount();
    public function setSize($size);
    public function getSize();
    public function setIndexSize($size);
    public function getIndexSize();
    public function setCreationDate($date);
    public function getCreationDate();
    public function setSchemaUpdateDate($date);
    public function getSchemaUpdateDate();
}

interface ISchemaExecutor {
    public function getAdapter();
    public function getTableStats($name);

    public function exists($name);
    public function introspect($name);
    public function create(opal\rdbms\schema\ISchema $schema);
    public function alter($currentName, opal\rdbms\schema\ISchema $schema);
    public function rename($oldName, $newName);
    public function drop($name);

    public function setCharacterSet($name, $set, $collation=null, $convert=false);
    public function getCharacterSet($name);
    public function setCollation($name, $collation, $convert=false);
    public function getCollation($name);
}

interface IQueryExecutor {
    public function getAdapter();
    public function getQuery();
    public function getStatement();

// Table queries
    public function truncate($tableName);
    public function countTable($tableName);

// Query passthrough
    public function executeReadQuery($tableName, $forCount=false);
    public function executeUnionQuery($tableName, $forCount=false);
    public function buildUnionQuery($tableName, $forCount=false);
    public function executeLocalReadQuery($tableName, $forCount=false);
    public function buildLocalReadQuery($tableName, $forCount=false);
    public function executeRemoteJoinedReadQuery($tableName, $forCount=false);
    public function executeInsertQuery($tableName);
    public function executeBatchInsertQuery($tableName);
    public function executeReplaceQuery($tableName);
    public function executeBatchReplaceQuery($tableName);
    public function executeUpdateQuery($tableName);
    public function executeDeleteQuery($tableName);

// Remote data
    public function fetchRemoteJoinData($tableName, array $rows);
    public function fetchAttachmentData($tableName, array $rows);

// Subquery builders
    public function buildCorrelation(IStatement $stmt);
    public function buildJoin(IStatement $stmt);

// Fields
    public function defineField(opal\query\IField $field, $alias=null);
    public function defineFieldReference(opal\query\IField $field, $allowAlias=false, $forUpdateOrDelete=false);

// Clauses
    public function writeJoinClauseSection();
    public function writeJoinClauseList(opal\query\IClauseList $list);
    public function writeWhereClauseSection(array $remoteJoinData=null, $forUpdateOrDelete=false);
    public function writeWhereClauseList(opal\query\IClauseList $list, array $remoteJoinData=null, $forUpdateOrDelete=false);
    public function writeHavingClauseSection();
    public function writeHavingClauseList(opal\query\IClauseList $list);

    public function defineClauseList(opal\query\IClauseList $list, array $remoteJoinData=null, $allowAlias=false, $forUpdateOrDelete=false);
    public function defineClause(opal\query\IClause $clause, array $remoteJoinData=null, $allowAlias=false, $forUpdateOrDelete=false);
    public function defineClauseCorrelation(opal\query\IField $field, $fieldString, $operator, opal\query\ICorrelationQuery $query, $allowAlias=false);
    public function defineClauseLocalCorrelation(opal\query\IField $field, $fieldString, $operator, opal\query\ICorrelationQuery $query);
    public function defineClauseRemoteCorrelation(opal\query\IField $field, $fieldString, $operator, opal\query\ICorrelationQuery $query, $allowAlias=false);
    public function defineClauseExpression(opal\query\IField $field, $fieldString, $operator, $value, $allowAlias=false);
    public function normalizeArrayClauseValue($value, $allowAlias=false);
    public function normalizeScalarClauseValue($value, $allowAlias=false);

// Expression
    public function defineExpression(opal\query\IExpression $expression);

// Group, order, limit
    public function writeGroupSection();
    public function writeOrderSection($forUpdateOrDelete=false);
    public function writeLimitSection($forUpdateOrDelete=false);
    public function defineLimit($limit, $offset=null);
}

interface IAlias {
    const IDENTIFIER = true;
    const NONE = false;
    const DEFINITION = 'def';
    const DEEP_DEFINITION = 'deepdef';
}