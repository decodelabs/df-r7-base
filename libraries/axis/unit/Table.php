<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\unit;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\R7\Legacy;

use df\axis;
use df\mesh;
use df\opal;

abstract class Table implements
    axis\ISchemaBasedStorageUnit,
    mesh\entity\IActiveParentEntity,
    opal\query\IEntryPoint,
    opal\query\IIntegralAdapter,
    opal\query\IPaginatingAdapter,
    Dumpable
{
    use axis\TUnit;
    use axis\TAdapterBasedStorageUnit;
    use axis\TSchemaBasedStorageUnit;

    public const NAME_FIELD = null;
    public const KEY_NAME = null;
    public const PRIORITY_FIELDS = null;
    public const BROADCAST_HOOK_EVENTS = true;
    public const DEFAULT_RECORD_CLASS = 'df\\opal\\record\\Base';

    public const ORDERABLE_FIELDS = null;
    public const DEFAULT_ORDER = null;
    public const SEARCH_FIELDS = null;

    private $_recordClass;
    private $_schema;

    private array $_defaultSearchFields;

    public function __construct(axis\IModel $model)
    {
        $this->_model = $model;
        $this->_loadAdapter();
    }

    public function getUnitType()
    {
        return 'table';
    }

    public function getStorageGroupName()
    {
        return $this->_adapter->getStorageGroupName();
    }

    public function getStorageBackendName()
    {
        $output = $this->_model->getModelName() . '_' . $this->getCanonicalUnitName();

        if ($this->_shouldPrefixNames()) {
            $output = Legacy::getUniquePrefix() . '_' . $output;
        }

        return $output;
    }

    public function getRelationUnit($fieldName)
    {
        $schema = $this->getUnitSchema();
        $field = $schema->getField($fieldName);

        if (!$field instanceof axis\schema\IRelationField) {
            throw Exceptional::Logic(
                'Unit ' . $this->getUnitId() . ' does not have a relation field named ' . $fieldName
            );
        }

        return $field->getTargetUnit();
    }

    public function getBridgeUnit($fieldName)
    {
        $schema = $this->getUnitSchema();
        $field = $schema->getField($fieldName);

        if (!$field instanceof axis\schema\IBridgedRelationField) {
            throw Exceptional::Logic(
                'Unit ' . $this->getUnitId() . ' does not have a bridge field named ' . $fieldName
            );
        }

        return $field->getBridgeUnit();
    }

    // Schema
    public function getUnitSchema()
    {
        if ($this->_schema === null) {
            $this->_schema = $this->_model->getSchemaManager()->fetchFor($this);
        }

        return $this->_schema;
    }

    public function getTransientUnitSchema($force = false)
    {
        if ($force) {
            $schema = $this->buildInitialSchema();
            $this->updateUnitSchema($schema);
            return $schema;
        }

        if ($this->_schema !== null) {
            return $this->_schema;
        }

        return $this->_model->getSchemaManager()->fetchFor($this, true);
    }

    public function buildInitialSchema()
    {
        return new axis\schema\Base($this, $this->getUnitName());
    }

    public function clearUnitSchemaCache()
    {
        $this->_schema = null;

        $cache = $this->_model->getSchemaManager()->getCache();
        $cache->delete($this->getUnitId());

        return $this;
    }

    public function updateUnitSchema(axis\schema\ISchema $schema)
    {
        $version = $schema->getVersion();

        if ($version === 0) {
            $this->createSchema($schema);
            $schema->iterateVersion();
        }

        while (true) {
            $version = $schema->getVersion();
            $func = 'updateSchema' . $version;

            if (!method_exists($this, $func)) {
                break;
            }

            $this->{$func}($schema);
            $schema->iterateVersion();
        }

        $schema->sanitize($this);

        return $this;
    }

    public function getDefinedUnitSchemaVersion()
    {
        $schema = $this->getTransientUnitSchema();
        $version = $schema->getVersion();

        do {
            $func = 'updateSchema' . $version++;
        } while (method_exists($this, $func));

        return $version - 1;
    }

    abstract protected function createSchema($schema);

    public function validateUnitSchema(axis\schema\ISchema $schema)
    {
        $manager = $this->_model->getSchemaManager();
        $manager->markTransient($this);

        $schema->validate($this);

        $manager->unmarkTransient($this);
        return $this;
    }

    public function ensureStorage()
    {
        $this->_adapter->ensureStorage();
        return $this;
    }

    public function createStorageFromSchema(axis\schema\ISchema $schema)
    {
        $this->_adapter->createStorageFromSchema($schema);
        return $this;
    }

    public function updateStorageFromSchema(axis\schema\ISchema $schema)
    {
        $this->_adapter->updateStorageFromSchema($schema);
        return $this;
    }


    public function destroyStorage()
    {
        $manager = $this->_model->getSchemaManager();
        $this->_adapter->destroyStorage();
        $manager->remove($this);

        return $this;
    }

    public function storageExists()
    {
        return $this->_adapter->storageExists();
    }



    // Query source
    public function getQuerySourceId()
    {
        return 'axis://' . $this->getModel()->getModelName() . '/' . ucfirst($this->getUnitName());
    }

    public function getQuerySourceAdapterHash()
    {
        return $this->_adapter->getQuerySourceAdapterHash();
    }

    public function getQuerySourceAdapterServerHash()
    {
        return $this->_adapter->getQuerySourceAdapterServerHash();
    }

    public function getQuerySourceDisplayName()
    {
        return $this->_adapter->getQuerySourceDisplayName();
    }

    public function getDelegateQueryAdapter()
    {
        return $this->_adapter->getDelegateQueryAdapter();
    }

    public function supportsQueryType($type)
    {
        return $this->_adapter->supportsQueryType($type);
    }

    public function supportsQueryFeature($feature)
    {
        switch ($feature) {
            case opal\query\IQueryFeatures::VALUE_PROCESSOR:
                return true;

            default:
                return $this->_adapter->supportsQueryFeature($feature);
        }
    }

    public function ensureStorageConsistency()
    {
        $this->_adapter->ensureStorageConsistency();
        return $this;
    }

    public function handleQueryException(opal\query\IQuery $query, \Throwable $e)
    {
        return $this->_adapter->handleQueryException($query, $e);
    }

    public function applyPagination(opal\query\IPaginator $paginator)
    {
        $schema = $this->getUnitSchema();
        $default = static::DEFAULT_ORDER;
        $fields = [];

        if (!empty(static::ORDERABLE_FIELDS)) {
            $fields = static::ORDERABLE_FIELDS;

            if (!is_array($fields)) {
                $fields = [(string)$fields];
            }

            if ($default === null) {
                $default = current($fields) . ' ASC';
            }
        } else {
            foreach ($schema->getFields() as $name => $field) {
                if (
                    $field instanceof opal\schema\INullPrimitiveField ||
                    /** @phpstan-ignore-next-line */
                    $field instanceof opal\schema\IManyRelationField
                ) {
                    continue;
                }

                if ($default === null
                || $default == 'id ASC') {
                    if (in_array($name, ['id', 'name', 'title'])) {
                        $default = $name . ' ASC';
                    } elseif ($name == 'date') {
                        $default = 'date DESC';
                    }
                }

                $fields[] = $name;
            }
        }

        if (!empty($fields)) {
            $paginator->addOrderableFields(...$fields);
        }

        if ($default !== null) {
            $paginator->setDefaultOrder($default);
        }

        return $this;
    }


    // Query proxy
    public function executeSelectQuery(opal\query\ISelectQuery $query)
    {
        return $this->_adapter->executeSelectQuery($query);
    }

    public function countSelectQuery(opal\query\ISelectQuery $query)
    {
        return $this->_adapter->countSelectQuery($query);
    }

    public function executeUnionQuery(opal\query\IUnionQuery $query)
    {
        return $this->_adapter->executeUnionQuery($query);
    }

    public function countUnionQuery(opal\query\IUnionQuery $query)
    {
        return $this->_adapter->countUnionQuery($query);
    }

    public function executeFetchQuery(opal\query\IFetchQuery $query)
    {
        return $this->_adapter->executeFetchQuery($query);
    }

    public function countFetchQuery(opal\query\IFetchQuery $query)
    {
        return $this->_adapter->countFetchQuery($query);
    }

    public function executeInsertQuery(opal\query\IInsertQuery $query)
    {
        return $this->_adapter->executeInsertQuery($query);
    }

    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query)
    {
        return $this->_adapter->executeBatchInsertQuery($query);
    }

    public function executeUpdateQuery(opal\query\IUpdateQuery $query)
    {
        return $this->_adapter->executeUpdateQuery($query);
    }

    public function executeDeleteQuery(opal\query\IDeleteQuery $query)
    {
        return $this->_adapter->executeDeleteQuery($query);
    }


    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows)
    {
        return $this->_adapter->fetchRemoteJoinData($join, $rows);
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows)
    {
        return $this->_adapter->fetchAttachmentData($attachment, $rows);
    }



    // Integral adapter
    public function getQueryAdapterSchema()
    {
        return $this->getUnitSchema();
    }

    public function prepareQueryClauseValue(opal\query\IField $field, $value)
    {
        $schema = $this->getUnitSchema();
        $name = $field->getName();

        if ($axisField = $schema->getField($name)) {
            return $axisField->deflateValue($axisField->sanitizeClauseValue($value));
        }


        // THIS IS A HACK - YOU NEED TO FIX THIS WHOLE THING!
        if (false !== strpos($name, '_')) {
            list($testName, ) = explode('_', $name, 2);
            $axisField = $schema->getField($testName);

            if ($axisField instanceof axis\schema\IRelationField) {
                $prepared = $axisField->deflateValue($axisField->sanitizeClauseValue($value));

                if (is_array($prepared)) {
                    if (isset($prepared[$name])) {
                        return $prepared[$name];
                    }
                } else {
                    return $prepared;
                }
            }
        }

        return $value;
    }

    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr = false)
    {
        if ((!$axisField = $this->getUnitSchema()->getField($field->getName()))
        || !$axisField instanceof opal\schema\IQueryClauseRewriterField) {
            throw Exceptional::{'df/axis/schema/Runtime'}(
                'Query field ' . $field->getName() . ' has no virtual field rewriter'
            );
        }

        return $axisField->rewriteVirtualQueryClause($parent, $field, $operator, $value, $isOr);
    }

    public function getDefaultSearchFields()
    {
        if (!isset($this->_defaultSearchFields)) {
            $fields = static::SEARCH_FIELDS;

            if (empty($fields)) {
                $schema = $this->getUnitSchema();
                $nameField = $this->getRecordNameField();
                $fields = [$nameField => 2];

                if ($nameField != 'id' && $schema->hasField('id')) {
                    $fields['id'] = 10;
                }
            }

            $this->_defaultSearchFields = $fields;
        }

        return $this->_defaultSearchFields;
    }

    public function getQueryResultValueProcessors(array $fields = null)
    {
        $schema = $this->getUnitSchema();

        if ($fields === null) {
            return $schema->getFields();
        }

        $output = [];

        foreach ($fields as $fieldName) {
            if ($field = $schema->getField($fieldName)) {
                $output[$fieldName] = $field;
            }
        }

        return $output;
    }


    public function applyQueryBlock(opal\query\IQuery $query, $name, array $args)
    {
        $method = 'apply' . ucfirst($name) . 'QueryBlock';

        if (!method_exists($this, $method)) {
            throw Exceptional::Logic(
                'Query block ' . $name . ' does not exist on ' . $this->getUnitId()
            );
        }

        array_unshift($args, $query);
        $this->{$method}(...$args);
        return $this;
    }

    public function applyRelationQueryBlock(opal\query\IQuery $query, opal\query\IField $relationField, $name, array $args)
    {
        $method = 'apply' . ucfirst($name) . 'RelationQueryBlock';

        if (!method_exists($this, $method)) {
            throw Exceptional::Logic(
                'Relation query block ' . $name . ' does not exist on ' . $this->getUnitId()
            );
        }

        $this->{$method}($query, $relationField, ...$args);
        return $this;
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
        if ($this->_recordClass === null) {
            $this->_recordClass = 'df\\apex\\models\\' . $this->_model->getModelName() . '\\' . $this->getUnitName() . '\\Record';

            if (!class_exists($this->_recordClass)) {
                $this->_recordClass = static::DEFAULT_RECORD_CLASS;
            } elseif (!is_subclass_of($this->_recordClass, static::DEFAULT_RECORD_CLASS)) {
                throw Exceptional::Logic(
                    $this->_recordClass . ' is not a valid record class for unit ' . $this->getUnitId()
                );
            }
        }

        return new $this->_recordClass($this, $values, array_keys($this->getUnitSchema()->getFields()));
    }

    public function newPartial(array $values = null)
    {
        return new opal\record\Partial($this, $values);
    }

    public function shouldRecordsBroadcastHookEvents()
    {
        return (bool)static::BROADCAST_HOOK_EVENTS;
    }



    // Query blocks
    public function applyLinkRelationQueryBlock(opal\query\IReadQuery $query, opal\query\IField $relationField, array $extraFields = null)
    {
        $schema = $this->getUnitSchema();
        $primaries = $schema->getPrimaryFields();
        $name = $this->getRecordNameField();
        $priority = $this->getRecordPriorityFields();
        $rName = $relationField->getName();

        if (!$primaries) {
            throw Exceptional::Logic(
                'Unit ' . $this->getUnitId() . ' does not have a primary index'
            );
        }


        $fields = [];
        $combine = [];
        $firstPrimary = null;

        foreach ($primaries as $qName => $field) {
            $firstPrimary = $qName;
            $fields[$qName] = $qName . ' as ' . $rName . '|' . $qName;
            $combine[$qName] = $rName . '|' . $qName . ' as ' . $qName;
        }

        foreach ($priority as $pName) {
            if (isset($primaries[$name])) {
                continue;
            }

            $fields[$pName] = $pName . ' as ' . $rName . '|' . $pName;
            $combine[$pName] = $rName . '|' . $pName . ' as ' . $pName;
        }


        if (!empty($extraFields)) {
            foreach ($extraFields as $extraField) {
                $parts = explode(' as ', $extraField);
                $fieldName = array_shift($parts);
                $alias = $parts[0] ?? $fieldName;

                $fields[$fieldName] = $fieldName . ' as ' . $rName . '|' . $alias;
                $combine[$fieldName] = $rName . '|' . $alias . ' as ' . $alias;
            }
        }

        if ($query instanceof opal\query\ISelectQuery) {
            $query->leftJoinRelation($relationField, $fields)
                ->combine($combine)
                    ->nullOn($firstPrimary)
                    ->asOne($rName)
                ->paginate()
                    ->addOrderableFields($rName . '|' . $name . ' as ' . $rName)
                    ->end();
        } elseif ($query instanceof opal\query\IFetchQuery) {
            $query->populateSelect($relationField, array_keys($fields));
        }

        return $this;
    }


    // Entry point
    public function select(...$fields)
    {
        return opal\query\Initiator::factory()
            ->beginSelect($fields)
            ->from($this, $this->getCanonicalUnitName());
    }

    public function selectDistinct(...$fields)
    {
        return opal\query\Initiator::factory()
            ->beginSelect($fields, true)
            ->from($this, $this->getCanonicalUnitName());
    }

    public function countAll()
    {
        return $this->select()->count();
    }

    public function countAllDistinct()
    {
        return $this->selectDistinct()->count();
    }

    public function union(...$fields)
    {
        return opal\query\Initiator::factory()
            ->beginUnion()
            ->with($fields)
            ->from($this);
    }

    public function fetch()
    {
        return opal\query\Initiator::factory()
            ->beginFetch()
            ->from($this, $this->getCanonicalUnitName());
    }

    public function fetchByPrimary($keys)
    {
        if ($keys instanceof opal\record\IRecord
        && $keys->getAdapter() === $this
        && !$keys->isNew()) {
            return $keys;
        }

        $query = $this->fetch();
        $primaryKeySet = null;

        if (is_string($keys) && substr($keys, 0, 7) == 'keySet?') {
            $primaryKeySet = opal\record\PrimaryKeySet::fromEntityId($keys);
        } elseif ($keys instanceof opal\record\IPrimaryKeySet) {
            $primaryKeySet = $keys;
        }

        if ($primaryKeySet) {
            foreach ($primaryKeySet->toArray() as $key => $value) {
                $query->where($key, '=', $value);
            }
        } else {
            if (!is_array($keys)) {
                $keys = func_get_args();
            }

            if (!$index = $this->getUnitSchema()->getPrimaryIndex()) {
                return null;
            }

            foreach (array_keys($index->getFields()) as $i => $primaryField) {
                $value = array_shift($keys);
                $query->where($primaryField, '=', $value);
            }
        }

        return $query->toRow();
    }

    public function insert($row)
    {
        return opal\query\Initiator::factory()
            ->beginInsert($row)
            ->into($this, $this->getCanonicalUnitName());
    }

    public function batchInsert($rows = [])
    {
        return opal\query\Initiator::factory()
            ->beginBatchInsert($rows)
            ->into($this, $this->getCanonicalUnitName());
    }

    public function replace($row)
    {
        return opal\query\Initiator::factory()
            ->beginReplace($row)
            ->in($this, $this->getCanonicalUnitName());
    }

    public function batchReplace($rows = [])
    {
        return opal\query\Initiator::factory()
            ->beginBatchReplace($rows)
            ->in($this, $this->getCanonicalUnitName());
    }

    public function update(array $valueMap = null)
    {
        return opal\query\Initiator::factory()
            ->beginUpdate($valueMap)
            ->in($this, $this->getCanonicalUnitName());
    }

    public function delete()
    {
        return opal\query\Initiator::factory()
            ->beginDelete()
            ->from($this, $this->getCanonicalUnitName());
    }

    public function newTransaction(): mesh\job\ITransaction
    {
        return new opal\query\Transaction($this);
    }



    // Mesh
    public function fetchSubEntity(mesh\IManager $manager, array $node)
    {
        switch ($node['type']) {
            case 'Record':
                if ($node['id'] == '*') {
                    return $this->newRecord();
                }

                return $this->fetchByPrimary($node['id']);

            case 'Schema':
                return $this->getUnitSchema();
        }
    }

    public function getSubEntityLocator(mesh\entity\IEntity $entity)
    {
        if ($entity instanceof opal\record\IPrimaryKeySetProvider) {
            $output = new mesh\entity\Locator(
                'axis://' . $this->getModel()->getModelName() . '/' . ucfirst($this->getUnitName())
            );

            $id = $entity->getPrimaryKeySet()->getEntityId();

            if (empty($id)) {
                $id = '*';
            }

            $output->setId($id);

            return $output;
        }

        throw Exceptional::{'df/mesh/entity/UnexpectedValue'}(
            'Unknown entity type',
            null,
            $entity
        );
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*type' => $this->getUnitType(),
            '*unitId' => $this->getUnitId(),
            '*adapter' => $this->_adapter
        ];
    }
}
