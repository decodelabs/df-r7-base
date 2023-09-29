<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\unit\table\adapter;

use DecodeLabs\Glitch\Dumpable;
use df\axis;
use df\opal;

use df\user;

class Rdbms implements
    axis\ISchemaProviderAdapter,
    axis\IConnectionProxyAdapter,
    axis\IIntrospectableAdapter,
    opal\query\IAdapter,
    Dumpable
{
    use user\TAccessLock;

    protected $_connection;
    protected $_querySourceAdapter;
    protected $_unit;

    public function __construct(axis\IAdapterBasedStorageUnit $unit)
    {
        $this->_unit = $unit;
    }

    public function getDisplayName(): string
    {
        return 'Rdbms';
    }

    public function getUnit()
    {
        return $this->_unit;
    }

    public function getConnection()
    {
        if (!$this->_connection) {
            $settings = $this->_unit->getUnitSettings();
            $dsn = opal\rdbms\Dsn::factory($settings['dsn']);
            $this->_connection = opal\rdbms\adapter\Base::factory($dsn, true);
        }

        return $this->_connection;
    }

    public function getConnectionDisplayName()
    {
        return $this->getConnection()->getDsn()->getDisplayString();
    }

    public function getStorageGroupName()
    {
        $output = $this->getConnection()->getDsn()->getDatabase();

        if (substr($output, -3) == '.db') {
            $output = substr($output, 0, -3);
        }

        return $output;
    }

    // Query source
    public function getQuerySourceId()
    {
        return 'axis://' . $this->_unit->getModel()->getModelName() . '/' . ucfirst($this->_unit->getUnitName());
    }

    public function getQuerySourceDisplayName()
    {
        return $this->_unit->getUnitId();
    }

    public function getQuerySourceAdapterHash()
    {
        return $this->getConnection()->getDsnHash();
    }

    public function getQuerySourceAdapterServerHash()
    {
        return $this->getConnection()->getServerDsnHash();
    }

    public function getQuerySourceAdapter()
    {
        if (!$this->_querySourceAdapter) {
            $this->_querySourceAdapter = $this->getConnection()->getTable($this->_unit->getStorageBackendName());
        }

        return $this->_querySourceAdapter;
    }

    public function getDelegateQueryAdapter()
    {
        return $this->getQuerySourceAdapter()->getDelegateQueryAdapter();
    }

    public function supportsQueryType($type)
    {
        return $this->getQuerySourceAdapter()->supportsQueryType($type);
    }

    public function supportsQueryFeature($feature)
    {
        return $this->getQuerySourceAdapter()->supportsQueryFeature($feature);
    }



    // Query proxy
    public function executeSelectQuery(opal\query\ISelectQuery $query)
    {
        return $this->getQuerySourceAdapter()->executeSelectQuery($query);
    }

    public function countSelectQuery(opal\query\ISelectQuery $query)
    {
        return $this->getQuerySourceAdapter()->countSelectQuery($query);
    }

    public function executeUnionQuery(opal\query\IUnionQuery $query)
    {
        return $this->getQuerySourceAdapter()->executeUnionQuery($query);
    }

    public function countUnionQuery(opal\query\IUnionQuery $query)
    {
        return $this->getQuerySourceAdapter()->countUnionQuery($query);
    }

    public function executeFetchQuery(opal\query\IFetchQuery $query)
    {
        return $this->getQuerySourceAdapter()->executeFetchQuery($query);
    }

    public function countFetchQuery(opal\query\IFetchQuery $query)
    {
        return $this->getQuerySourceAdapter()->countFetchQuery($query);
    }

    public function executeInsertQuery(opal\query\IInsertQuery $query)
    {
        return $this->getQuerySourceAdapter()->executeInsertQuery($query);
    }

    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query)
    {
        return $this->getQuerySourceAdapter()->executeBatchInsertQuery($query);
    }

    public function executeUpdateQuery(opal\query\IUpdateQuery $query)
    {
        return $this->getQuerySourceAdapter()->executeUpdateQuery($query);
    }

    public function executeDeleteQuery(opal\query\IDeleteQuery $query)
    {
        return $this->getQuerySourceAdapter()->executeDeleteQuery($query);
    }


    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows)
    {
        return $this->getQuerySourceAdapter()->fetchRemoteJoinData($join, $rows);
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows)
    {
        return $this->getQuerySourceAdapter()->fetchAttachmentData($attachment, $rows);
    }




    // Transactions
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
        return $this->getQuerySourceAdapter()->begin();
    }

    public function commit()
    {
        return $this->getQuerySourceAdapter()->commit();
    }

    public function rollback()
    {
        return $this->getQuerySourceAdapter()->rollback();
    }


    // Record
    public function newRecord(array $values = null)
    {
        return $this->_unit->newRecord($values);
    }

    public function newPartial(array $values = null)
    {
        return $this->_unit->newPartial($values);
    }

    public function shouldRecordsBroadcastHookEvents()
    {
        return $this->_unit->shouldRecordsBroadcastHookEvents();
    }


    // Create
    public function ensureStorage()
    {
        if ($this->storageExists()) {
            return false;
        }

        $schema = $this->_unit->getTransientUnitSchema();
        $this->createStorageFromSchema($schema);

        return $this;
    }

    public function createStorageFromSchema(axis\schema\ISchema $axisSchema)
    {
        $adapter = $this->getConnection();
        $table = $adapter->getTable($this->_unit->getStorageBackendName());

        $translator = new axis\schema\translator\Rdbms($this->_unit, $adapter, $axisSchema);
        $dbSchema = $translator->createFreshTargetSchema();

        return $table->create($dbSchema);
    }

    public function updateStorageFromSchema(axis\schema\ISchema $axisSchema)
    {
        $adapter = $this->getConnection();
        $table = $adapter->getTable($this->_unit->getStorageBackendName());

        $translator = new axis\schema\translator\Rdbms($this->_unit, $adapter, $axisSchema, $table->getSchema());
        $dbSchema = $translator->updateTargetSchema();

        if ($dbSchema->hasChanged()) {
            return $table->alter($dbSchema);
        }

        return $table;
    }

    public function destroyStorage()
    {
        $this->getQuerySourceAdapter()->drop();
        return $this;
    }

    public function storageExists()
    {
        return $this->getQuerySourceAdapter()->exists();
    }


    // Query exceptions
    public function handleQueryException(opal\query\IQuery $query, \Throwable $e)
    {
        // Table not found
        if ($e instanceof opal\rdbms\TableNotFoundException) {
            $table = $e->getData()['table'] ?? null;

            if ($table !== null && strtolower((string)$table) == strtolower($this->_unit->getStorageBackendName())) {
                $this->ensureStorageConsistency();
                return true;
            }
        }

        return false;
    }

    public function ensureStorageConsistency()
    {
        $model = $this->_unit->getModel();
        $manager = $model->getSchemaManager();

        $idList = $manager->fetchStoredUnitList();
        $tableList = $this->getStorageList();
        $update = [];
        $remove = [];

        $unitId = $this->_unit->getUnitId();

        if (!in_array($unitId, $idList)) {
            $idList[] = $unitId;
        }

        foreach ($idList as $unitId) {
            try {
                $unit = $model->loadUnitFromId($unitId);
            } catch (axis\Exception $e) {
                $remove[] = $unitId;
                continue;
            }

            $backendName = $unit->getStorageBackendName();

            if (!in_array($backendName, $tableList)) {
                $update[$backendName] = $unit;
            }
        }

        if (!empty($remove)) {
            $manager->clearCache();

            foreach ($remove as $unitId) {
                $manager->removeId($unitId);
            }
        }

        if (!empty($update)) {
            $manager->clearCache();

            foreach ($update as $name => $unit) {
                $manager->remove($unit);
                $manager->fetchFor($unit);
            }
        }

        return $this;
    }



    // Introspection
    public function getStorageList()
    {
        return $this->getConnection()->getDatabase()->getTableList();
    }

    public function describeStorage($name = null)
    {
        if ($name === null) {
            $name = $this->_unit->getStorageBackendName();
        }

        $table = $this->getConnection()->getTable($name);
        $stats = $table->getStats();

        return new axis\introspector\StorageDescriber(
            $name,
            $this->getConnection()->getAdapterName(),
            $stats->getRowCount(),
            $stats->getSize(),
            $stats->getIndexSize(),
            $stats->getCreationDate()
        );
    }

    public function destroyDescribedStorage($name)
    {
        if ($name instanceof axis\introspector\IStorageDescriber) {
            $name = $name->getName();
        }

        $this->getConnection()->getTable($name)->drop();
        return $this;
    }


    // Access
    public function getAccessLockDomain()
    {
        return $this->_unit->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action = null)
    {
        return $this->_unit->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action = null)
    {
        return $this->_unit->getDefaultAccess($action);
    }

    public function getAccessLockId()
    {
        return $this->_unit->getAccessLockId();
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '%name' => $this->getDisplayName(),
            '*unit' => $this->_unit->getUnitId()
        ];
    }
}
