<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;

use df\mesh;
use df\opal;
use df\user;

class Table implements ITable, Dumpable
{
    use opal\query\TQuery_EntryPoint;
    use user\TAccessLock;

    protected $_adapter;
    protected $_name;
    protected $_querySourceId;

    public function __construct(opal\rdbms\IAdapter $adapter, string $name)
    {
        $this->_adapter = $adapter;
        $this->_setName($name);
    }

    public function getAdapter()
    {
        return $this->_adapter;
    }

    protected function _setName($name)
    {
        $this->_name = $name;
        $this->_querySourceId = 'opal://rdbms/' . $this->_adapter->getServerType() . ':"' . addslashes($this->_adapter->getDsn()->getConnectionString()) . '"/Table:' . $this->_name;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getSchema()
    {
        $schema = SchemaExecutor::factory($this->_adapter)->introspect($this->_name);
        $schema->acceptChanges()->isAudited(true);

        return $schema;
    }

    public function getStats()
    {
        return SchemaExecutor::factory($this->_adapter)->getTableStats($this->_name);
    }


    // Query source
    public function getQuerySourceId()
    {
        return $this->_querySourceId;
    }

    public function getQuerySourceAdapterHash()
    {
        return $this->_adapter->getDsnHash();
    }

    public function getQuerySourceAdapterServerHash()
    {
        return $this->_adapter->getServerDsnHash();
    }

    public function getQuerySourceDisplayName()
    {
        return $this->_adapter->getDsn()->getDisplayString() . '/' . $this->_name;
    }

    public function getDelegateQueryAdapter()
    {
        return $this;
    }

    public function getDatabaseName()
    {
        return $this->_adapter->getDsn()->getDatabase();
    }

    public function supportsQueryType($type)
    {
        switch ($type) {
            case opal\query\IQueryTypes::SELECT:
            case opal\query\IQueryTypes::UNION:
            case opal\query\IQueryTypes::FETCH:
            case opal\query\IQueryTypes::INSERT:
            case opal\query\IQueryTypes::BATCH_INSERT:
            case opal\query\IQueryTypes::REPLACE:
            case opal\query\IQueryTypes::BATCH_REPLACE:
            case opal\query\IQueryTypes::UPDATE:
            case opal\query\IQueryTypes::DELETE:

            case opal\query\IQueryTypes::CORRELATION:
            case opal\query\IQueryTypes::DERIVATION:

            case opal\query\IQueryTypes::JOIN:
            case opal\query\IQueryTypes::JOIN_CONSTRAINT:
            case opal\query\IQueryTypes::REMOTE_JOIN:

            case opal\query\IQueryTypes::SELECT_ATTACH:
            case opal\query\IQueryTypes::FETCH_ATTACH:
                return true;

            default:
                return false;
        }
    }

    public function supportsQueryFeature($feature)
    {
        switch ($feature) {
            case opal\query\IQueryFeatures::AGGREGATE:
            case opal\query\IQueryFeatures::WHERE_CLAUSE:
            case opal\query\IQueryFeatures::GROUP_DIRECTIVE:
            case opal\query\IQueryFeatures::HAVING_CLAUSE:
            case opal\query\IQueryFeatures::ORDER_DIRECTIVE:
            case opal\query\IQueryFeatures::LIMIT:
            case opal\query\IQueryFeatures::OFFSET:
            case opal\query\IQueryFeatures::TRANSACTION:
                return true;

            default:
                return false;
        }
    }

    public function handleQueryException(opal\query\IQuery $query, \Throwable $e)
    {
        return false;
    }

    public function ensureStorageConsistency()
    {
    }


    ## SCHEMA ##
    public function exists()
    {
        return SchemaExecutor::factory($this->_adapter)->exists($this->_name);
    }


    public function create(opal\rdbms\schema\ISchema $schema, $dropIfExists = false)
    {
        if ($schema->getName() != $this->_name) {
            throw Exceptional::{'df/opal/rdbms/TableNotFound,NotFound'}(
                'Schema name ' . $schema->getName() . ' does not match table name ' . $this->_name,
                null,
                [
                    'database' => $this->_adapter->getDsn()->getDatabase(),
                    'table' => $this->_name
                ]
            );
        }

        $exec = SchemaExecutor::factory($this->_adapter);

        if ($exec->exists($this->_name)) {
            if ($dropIfExists) {
                $exec->drop($this->_name);
            } else {
                throw Exceptional::{'df/opal/rdbms/TableConflict'}(
                    'Table ' . $schema->getName() . ' already exists',
                    null,
                    [
                        'database' => $this->_adapter->getDsn()->getDatabase(),
                        'table' => $this->_name
                    ]
                );
            }
        }

        $exec->create($schema);
        return $this;
    }

    public function alter(opal\rdbms\schema\ISchema $schema)
    {
        $schema->normalize();
        SchemaExecutor::factory($this->_adapter)->alter($this->_name, $schema);
        $this->_setName($schema->getName());
        return $this;
    }

    public function rename($newName)
    {
        SchemaExecutor::factory($this->_adapter)->rename($this->_name, $newName);
        $this->_setName($newName);
        return $this;
    }

    public function copy($newName)
    {
        $schema = clone $this->getSchema();
        $schema->setName($newName);

        $newTable = $this->_adapter->createTable($schema);
        $insert = $newTable->batchInsert();

        foreach ($this->select() as $row) {
            $insert->addRow($row);
        }

        $insert->execute();
        return $newTable;
    }

    public function drop()
    {
        SchemaExecutor::factory($this->_adapter)->drop($this->_name);
        return $this;
    }

    public function truncate()
    {
        QueryExecutor::factory($this->_adapter)->truncate($this->_name);
        return $this;
    }


    // Lock
    public function lock()
    {
        return $this->_adapter->lockTable($this->_name);
    }

    public function unlock()
    {
        return $this->_adapter->unlockTable($this->_name);
    }




    // Character sets
    public function setCharacterSet($set, $collation = null, $convert = false)
    {
        if (is_bool($collation)) {
            $convert = $collation;
            $collation = null;
        }

        SchemaExecutor::factory($this->_adapter)->setCharacterSet($this->_name, $set, $collation, $convert);
        return $this;
    }

    public function getCharacterSet()
    {
        return SchemaExecutor::factory($this->_adapter)->getCharacterSet($this->_name);
    }

    public function setCollation($collation, $convert = false)
    {
        SchemaExecutor::factory($this->_adapter)->setCollation($this->_name, $collation, $convert);
        return $this;
    }

    public function getCollation()
    {
        return SchemaExecutor::factory($this->_adapter)->getCollation($this->_name);
    }




    // Count
    public function count(): int
    {
        return QueryExecutor::factory($this->_adapter)->countTable($this->_name);
    }



    ## Queries ##
    public function executeSelectQuery(opal\query\ISelectQuery $query)
    {
        return QueryExecutor::factory($this->_adapter, $query)
            ->executeReadQuery($this->_name);
    }

    public function countSelectQuery(opal\query\ISelectQuery $query)
    {
        $row = QueryExecutor::factory($this->_adapter, $query)
            ->executeReadQuery($this->_name, true)
            ->getCurrent();

        if (isset($row['count'])) {
            return $row['count'];
        }

        return 0;
    }

    public function executeUnionQuery(opal\query\IUnionQuery $query)
    {
        return QueryExecutor::factory($this->_adapter, $query)
            ->executeUnionQuery($this->_name);
    }

    public function countUnionQuery(opal\query\IUnionQuery $query)
    {
        $row = QueryExecutor::factory($this->_adapter, $query)
            ->executeUnionQuery($this->_name, true)
            ->getCurrent();

        if (isset($row['count'])) {
            return $row['count'];
        }

        return 0;
    }

    public function executeFetchQuery(opal\query\IFetchQuery $query)
    {
        return QueryExecutor::factory($this->_adapter, $query)
            ->executeReadQuery($this->_name);
    }

    public function countFetchQuery(opal\query\IFetchQuery $query)
    {
        $row = QueryExecutor::factory($this->_adapter, $query)
            ->executeReadQuery($this->_name, true)
            ->getCurrent();

        if (isset($row['count'])) {
            return $row['count'];
        }

        return 0;
    }



    // Insert query
    public function executeInsertQuery(opal\query\IInsertQuery $query)
    {
        return QueryExecutor::factory($this->_adapter, $query)
            ->executeInsertQuery($this->_name);
    }

    // Batch insert query
    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query)
    {
        return QueryExecutor::factory($this->_adapter, $query)
            ->executeBatchInsertQuery($this->_name);
    }

    // Update query
    public function executeUpdateQuery(opal\query\IUpdateQuery $query)
    {
        return QueryExecutor::factory($this->_adapter, $query)
            ->executeUpdateQuery($this->_name);
    }

    // Delete query
    public function executeDeleteQuery(opal\query\IDeleteQuery $query)
    {
        return QueryExecutor::factory($this->_adapter, $query)
            ->executeDeleteQuery($this->_name);
    }

    // Remote data
    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows)
    {
        return QueryExecutor::factory($this->_adapter, $join)
            ->fetchRemoteJoinData($this->_name, $rows);
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows)
    {
        return QueryExecutor::factory($this->_adapter, $attachment)
            ->fetchAttachmentData($this->_name, $rows);
    }



    // Transaction
    public function getTransactionId()
    {
        return $this->getQuerySourceAdapterHash();
    }

    public function getJobAdapterId()
    {
        return $this->getQuerySourceId();
    }

    public function begin()
    {
        return $this->_adapter->begin();
    }

    public function commit()
    {
        return $this->_adapter->commit();
    }

    public function rollback()
    {
        return $this->_adapter->rollback();
    }


    // Record
    public function newRecord(array $values = null)
    {
        return new opal\record\Base($this, $values);
    }

    public function newPartial(array $values = null)
    {
        return new opal\record\Partial($this, $values);
    }

    public function shouldRecordsBroadcastHookEvents()
    {
        return false;
    }


    // Access
    public function getAccessLockDomain()
    {
        return 'rdbms';
    }

    public function lookupAccessKey(array $keys, $action = null)
    {
        Glitch::incomplete([$keys, $action]);
    }

    public function getDefaultAccess($action = null)
    {
        return true;
    }

    public function getAccessLockId()
    {
        return $this->_querySourceId;
    }


    // Mesh
    public function getEntityLocator()
    {
        $output = $this->_adapter->getEntityLocator();
        $output->addNode(null, 'Table', $this->getName());
        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*adapter' => $this->_adapter->getDsn()->getDisplayString(),
            '*name' => $this->_name
        ];
    }
}
