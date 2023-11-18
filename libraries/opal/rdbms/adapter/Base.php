<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\adapter;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Guidance\Uuid;
use df\core;
use df\mesh;
use df\opal;

abstract class Base implements opal\rdbms\IAdapter, Dumpable
{
    public const AUTO_INCREMENT = 1;
    public const SEQUENCES = 2;
    public const STORED_PROCEDURES = 3;
    public const VIEWS = 4;
    public const NESTED_TRANSACTIONS = 5;
    public const TRIGGERS = 6;
    public const FOREIGN_KEYS = 7;
    public const UPDATE_LIMIT = 8;
    public const DELETE_LIMIT = 9;

    private static $_connections = [];

    protected $_dsn;
    protected $_connection;
    protected $_transactionLevel = 0;
    protected $_isClone = false;
    protected $_support = [];

    public static function factory($dsn, $autoCreate = false)
    {
        $dsn = opal\rdbms\Dsn::factory($dsn);
        $hash = $dsn->getHash();

        if (isset(self::$_connections[$hash])) {
            if (self::$_connections[$hash]->isConnected()) {
                return self::$_connections[$hash];
            }

            unset(self::$_connections[$hash]);
        }

        $adapterName = ucfirst($dsn->getAdapter());
        $class = 'df\\opal\\rdbms\\adapter\\' . $adapterName;

        if (!class_exists($class)) {
            throw Exceptional::{'df/opal/rdbms/AdapterNotFound,NotFound'}(
                'RDBMS adapter ' . $adapterName . ' could not be found'
            );
        }

        self::$_connections[$hash] = new $class($dsn, $autoCreate);
        return self::$_connections[$hash];
    }

    public static function destroyConnection($hash)
    {
        if ($hash instanceof opal\rdbms\IDsn) {
            $hash = $hash->getHash();
        }

        if (isset(self::$_connections[$hash])) {
            unset(self::$_connections[$hash]);
        }
    }


    protected function __construct(opal\rdbms\IDsn $dsn, $autoCreate = false)
    {
        $this->_dsn = $dsn;

        if ($autoCreate) {
            try {
                $this->_connect();
            } catch (opal\rdbms\DatabaseNotFoundException $e) {
                $this->_connect(true);
                $this->_createDb();
                $this->_closeConnection();
                $this->_connect();
            }
        } else {
            $this->_connect();
        }
    }

    public function __destruct()
    {
        $this->_closeConnection();
    }

    public function __clone()
    {
        $this->_dsn = clone $this->_dsn;
        $this->_isClone = true;
        $this->_transactionLevel = 0;
        $this->_connection = null;
        $this->_connect();
    }

    public function getAdapterName()
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }


    // Connection
    public function isConnected()
    {
        return $this->_connection !== null;
    }

    public function isClone()
    {
        return $this->_isClone;
    }

    public function closeConnection()
    {
        $this->_closeConnection();

        if (!$this->_isClone) {
            self::destroyConnection($this->_dsn->getHash());
        }

        return $this;
    }

    public function getConnection()
    {
        return $this->_connection;
    }

    public function getDsn()
    {
        return $this->_dsn;
    }

    public function getDsnHash()
    {
        return $this->_dsn->getHash();
    }

    public function getServerDsnHash()
    {
        return $this->_dsn->getServerHash();
    }

    public function switchDatabase($newName)
    {
        $this->closeConnection();
        $this->_dsn->setDatabase($newName);
        $this->_connect();
        self::$_connections[$this->_dsn->getHash()] = $this;
        return $this;
    }

    public function supports($feature)
    {
        if (!isset($this->_support[$feature])) {
            $this->_support[$feature] = (bool)$this->_supports($feature);
        }

        return $this->_support[$feature];
    }

    public function getEncoding()
    {
        if (!$encoding = $this->_dsn->getOption('encoding')) {
            $encoding = 'utf8';
        }

        return $encoding;
    }


    // Transaction
    public function begin()
    {
        if ($this->_transactionLevel === 0 || $this->supports(self::NESTED_TRANSACTIONS)) {
            $this->_beginTransaction();
        }

        $this->_transactionLevel++;
        return $this;
    }

    public function commit()
    {
        if ($this->_transactionLevel > 0) {
            if ($this->_transactionLevel === 1 || $this->supports(self::NESTED_TRANSACTIONS)) {
                $this->_commitTransaction();
            }

            $this->_transactionLevel--;
        }

        return $this;
    }

    public function rollback()
    {
        if ($this->_transactionLevel > 0) {
            if ($this->_transactionLevel === 1 || $this->supports(self::NESTED_TRANSACTIONS)) {
                $this->_rollbackTransaction();
            }

            $this->_transactionLevel--;
        }

        return $this;
    }

    public function isTransactionOpen()
    {
        return $this->_transactionLevel > 0;
    }

    // Sanitize
    public function quoteTableAliasDefinition($alias)
    {
        return $this->quoteIdentifier($alias);
    }

    public function quoteTableAliasReference($alias)
    {
        return $this->quoteIdentifier($alias);
    }

    public function quoteFieldAliasDefinition($alias)
    {
        return $this->quoteValue($alias);
    }

    public function quoteFieldAliasReference($alias)
    {
        return $this->quoteIdentifier($alias);
    }

    public function prepareValue($value, opal\rdbms\schema\IField $field = null)
    {
        if (is_bool($value)) {
            $value = (int)$value;
        }

        if ($field !== null) {
            if (false !== ($preppedValue = $this->_prepareKnownValue($value, $field))) {
                return $preppedValue;
            }

            if ($field instanceof opal\rdbms\schema\field\Integer) {
                return (int)$value;
            }
        }

        if (is_numeric($value)) {
            return $value;
        } elseif ($value === null) {
            return 'NULL';
        }

        $value = $this->normalizeValue($value);
        return $this->quoteValue($value);
    }

    public function normalizeValue($value)
    {
        if ($value instanceof core\time\IDate) {
            $value = $this->_prepareDateValue($value);
        } elseif ($value instanceof Uuid) {
            return $value->getBytes();
        }

        return $value;
    }

    protected function _prepareKnownValue($value, opal\rdbms\schema\IField $field)
    {
        return false;
    }

    protected function _prepareDateValue(core\time\IDate $value)
    {
        return $value->toUtc()->format('Y-m-d H:i:s');
    }


    // Introspection
    public function getDatabaseList()
    {
        return $this->getDatabase()->getList();
    }

    public function databaseExists($name)
    {
        return $this->getDatabase()->exists($name);
    }



    public function getDatabase()
    {
        return opal\rdbms\Database::factory($this);
    }

    public function tableExists($name)
    {
        return $this->getTable($name)->exists();
    }



    public function getTable($name)
    {
        return new opal\rdbms\Table($this, $name);
    }

    public function createTable(opal\rdbms\schema\ISchema $schema, $dropIfExists = false)
    {
        $table = $this->getTable($schema->getName());
        $table->create($schema, $dropIfExists);

        return $table;
    }

    public function getSchema($name)
    {
        return $this->getTable($name)->getSchema();
    }


    // Mesh
    public function getEntityLocator()
    {
        return new mesh\entity\Locator('opal://rdbms/' . $this->getAdapterName() . ':"' . $this->getDsn()->getConnectionString() . '"');
    }

    public function fetchSubEntity(mesh\IManager $manager, array $node)
    {
        if ($node['id'] === null) {
            throw Exceptional::{'df/mesh/entity/NotFound'}(
                'Opal entities must be referenced with an id in it\'s locator'
            );
        }

        switch ($node['type']) {
            case 'Table':
                return $this->getTable($node['id']);

            case 'Schema':
                return $this->getTable($node['id'])->getSchema();
        }
    }


    // Stubs
    abstract protected function _connect($global = false);
    abstract protected function _createDb();
    abstract protected function _closeConnection();
    abstract protected function _supports($feature);
    abstract protected function _beginTransaction();
    abstract protected function _commitTransaction();
    abstract protected function _rollbackTransaction();


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->_dsn->getDisplayString();
    }
}





/**
 * PDO
 */
abstract class Base_Pdo extends opal\rdbms\adapter\Base
{
    protected $_affectedRows = 0;

    // Connection
    protected function _connect($global = false)
    {
        if ($this->_connection) {
            return;
        }

        if (!extension_loaded('pdo')) {
            throw Exceptional::{'df/opal/rdbms/AdapterNotFound,NotFound'}(
                'PDO is not currently available'
            );
        }

        $pdoType = strtolower($this->getServerType());

        if (!in_array($pdoType, \PDO::getAvailableDrivers())) {
            throw Exceptional::{'df/opal/rdbms/AdapterNotFound,NotFound'}(
                'PDO adapter ' . $pdoType . ' is not currently available'
            );
        }

        try {
            $connection = new \PDO(
                $this->_getPdoDsn($global),
                $this->_dsn->getUsername(),
                $this->_dsn->getPassword(),
                $this->_getPdoOptions()
            );

            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw $this->_getConnectionException($e->getCode(), $e->getMessage());
        }

        $this->_connection = $connection;
    }

    abstract protected function _getPdoDsn($global = false);
    abstract protected function _getPdoOptions();

    abstract public function _getConnectionException($code, $message);
    abstract public function _getQueryException($code, $message, $sql = null);

    protected function _closeConnection()
    {
        $this->_connection = null;
        return true;
    }


    // Transactions
    protected function _beginTransaction()
    {
        $this->_connection->beginTransaction();
    }

    protected function _commitTransaction()
    {
        $this->_connection->commit();
    }

    protected function _rollbackTransaction()
    {
        $this->_connection->rollBack();
    }



    // Query
    public function prepare($sql)
    {
        return new opal\rdbms\adapter\statement\Pdo($this, $sql);
    }

    public function executeSql($sql, $forWrite = false)
    {
        try {
            if ($forWrite) {
                $this->_affectedRows = $this->_connection->exec($sql);

                if ($this->_affectedRows === false) {
                    $this->_affectedRows = 0;
                    $info = $this->_connection->errorInfo();
                    throw $this->_getQueryException($info[1], $info[2], $sql);
                }

                return $this->_affectedRows;
            } else {
                return $this->_connection->query($sql);
            }
        } catch (\PDOException $e) {
            $info = $this->_connection->errorInfo();
            throw $this->_getQueryException($info[1], $info[2], $sql);
        }
    }

    public function getLastInsertId()
    {
        return $this->_connection->lastInsertId();
    }

    public function countAffectedRows()
    {
        return $this->_affectedRows;
    }
}
