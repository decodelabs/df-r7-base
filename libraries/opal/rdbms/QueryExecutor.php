<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch;
use df\opal;

abstract class QueryExecutor implements IQueryExecutor
{
    protected $_stmt;
    protected $_query;
    protected $_adapter;
    protected $_isMultiDb = false;

    public static function factory(IAdapter $adapter, opal\query\IQuery $query = null, IStatement $stmt = null)
    {
        $type = $adapter->getServerType();
        $class = 'df\\opal\\rdbms\\variant\\' . $type . '\\QueryExecutor';

        if (!class_exists($class)) {
            throw Exceptional::Runtime(
                'There is no query executor available for ' . $type
            );
        }

        return new $class($adapter, $query);
    }

    protected function __construct(IAdapter $adapter, opal\query\IQuery $query = null, IStatement $stmt = null)
    {
        $this->_adapter = $adapter;
        $this->_query = $query;

        if (!$stmt) {
            $stmt = $adapter->prepare('');
        }

        $this->_stmt = $stmt;

        if ($this->_query instanceof opal\query\IReadQuery) {
            $this->_stmt->isUnbuffered($this->_query->isUnbuffered());
        }
    }

    public function getAdapter()
    {
        return $this->_adapter;
    }

    public function getQuery()
    {
        return $this->_query;
    }

    public function getStatement()
    {
        return $this->_stmt;
    }


    // Count
    public function countTable($tableName)
    {
        $sql = 'SELECT COUNT(*) as total FROM ' . $this->_adapter->quoteIdentifier($tableName);
        $res = $this->_adapter->prepare($sql)->executeRead();
        $row = $res->extract();

        return (int)$row['total'];
    }


    // Read
    public function executeReadQuery($tableName, $forCount = false)
    {
        if ($this->_query->getSourceManager()->canQueryLocally()) {
            return $this->executeLocalReadQuery($tableName, $forCount);
        } else {
            return $this->executeRemoteJoinedReadQuery($tableName, $forCount);
        }
    }

    public function executeUnionQuery($tableName, $forCount = false)
    {
        return $this->buildUnionQuery($tableName, $forCount)->executeRead();
    }

    public function buildUnionQuery($tableName, $forCount = false)
    {
        $queries = $this->_query->getQueries();
        $sourceHash = null;
        $first = true;


        foreach ($queries as $query) {
            $source = $query->getSource();

            if ($sourceHash === null) {
                $sourceHash = $source->getHash();
            } elseif ($source->getHash() != $sourceHash) {
                throw Exceptional::{'df/opal/query/Logic'}(
                    'Union queries must all be on the same adapter'
                );
            }

            if ($first) {
                $this->_stmt->appendSql('(');
            } else {
                $this->_stmt->appendSql(')' . "\n" . 'UNION' . "\n" . '(');
            }

            $qExec = QueryExecutor::factory($this->_adapter, $query);
            $tableName = $query->getSource()->getAdapter()->getDelegateQueryAdapter()->getName();
            $qExec->buildLocalReadQuery($tableName, false);
            $statement = $qExec->getStatement();
            $this->_stmt->importBindings($statement);
            $this->_stmt->appendSql($statement->getSql());

            $first = false;
        }

        $this->_stmt->appendSql(')');

        if ($forCount) {
            $this->_stmt->prependSql('SELECT COUNT(*) as count FROM (')->appendSql(') as baseQuery');
        } else {
            $this->writeOrderSection();
            $this->writeLimitSection();
        }

        return $this->_stmt;
    }

    public function executeLocalReadQuery($tableName, $forCount = false)
    {
        $this->buildLocalReadQuery($tableName, $forCount);
        return $this->_stmt->executeRead();
    }

    public function buildLocalReadQuery($tableName, $forCount = false)
    {
        $sourceManager = $this->_query->getSourceManager();
        $source = $this->_query->getSource();
        $search = $this->_query->getSearch();
        $outFields = [];

        $this->_isMultiDb = $sourceManager->countSourceAdapters() > 1;

        // Fields
        foreach ($source->getDereferencedOutputFields() as $field) {
            if ($field instanceof opal\query\IAggregateField) {
                $fieldAlias = $field->getSource()->getAlias() . '.' . $field->getAlias();
            } else {
                $fieldAlias = $field->getQualifiedName();
            }

            $field->setLogicalAlias($fieldAlias);
            $outFields[] = $this->defineField($field, $fieldAlias);
        }


        // Joins
        $joinSql = null;

        if ($this->_query instanceof opal\query\IJoinProviderQuery) {
            // Build join statements
            foreach ($this->_query->getJoins() as $join) {
                $joinSource = $join->getSource();

                $jExec = QueryExecutor::factory($this->_adapter, $join);
                $jExec->_isMultiDb = $this->_isMultiDb;
                $joinSql .= "\n" . $jExec->buildJoin($this->_stmt);

                foreach ($joinSource->getDereferencedOutputFields() as $field) {
                    $fieldAlias = $field->getQualifiedName();
                    $outFields[] = $this->defineField($field, $fieldAlias);
                }
            }
        }


        // Attachments
        if (!$forCount && $this->_query instanceof opal\query\IAttachProviderQuery) {
            // Get fields that need to be fetched from source for attachment clauses
            foreach ($this->_query->getAttachments() as $attachment) {
                foreach ($attachment->getNonLocalFieldReferences() as $field) {
                    foreach ($field->dereference() as $derefField) {
                        $qName = $derefField->getQualifiedName();
                        $outFields[] = $this->defineField($derefField, $qName);
                    }
                }
            }
        }

        if (!$forCount) {
            $outFields = array_unique($outFields);
        }

        $distinct = $this->_query instanceof opal\query\IDistinctQuery && $this->_query->isDistinct() ? ' DISTINCT' : null;

        $this->_stmt->appendSql('SELECT' . $distinct . "\n" . '  ' . implode(',' . "\n" . '  ', $outFields) . "\n");

        if ($source->isDerived()) {
            $query = $source->getAdapter()->getDerivationQuery();
            $qExec = QueryExecutor::factory($this->_adapter, $query);
            $qExec->_isMultiDb = $this->_isMultiDb;
            $tableName = $query->getSource()->getAdapter()->getDelegateQueryAdapter()->getName();

            if ($query instanceof opal\query\ISelectQuery) {
                $qExec->buildLocalReadQuery($tableName, false);
            } elseif ($query instanceof opal\query\IUnionQuery) {
                $qExec->buildUnionQuery($tableName, false);
            } else {
                throw Exceptional::{'df/opal/query/Logic'}(
                    'Don\'t know how to derive from query type: ' . $query->getQueryType()
                );
            }

            $statement = $qExec->getStatement();
            $this->_stmt->importBindings($statement);
            $this->_stmt->appendSql('FROM (' . "\n" . '    ' . str_replace("\n", "\n    ", $statement->getSql()) . "\n" . ') ');
        } else {
            $this->_stmt->appendSql('FROM ' . $this->_adapter->quoteIdentifier($tableName) . ' ');
        }

        $this->_stmt->appendSql(
            'AS ' . $this->_adapter->quoteTableAliasDefinition($source->getAlias()) .
            $joinSql
        );

        $this->writeWhereClauseSection();
        $this->writeGroupSection();
        $this->writeHavingClauseSection();

        if ($forCount) {
            $this->_stmt->prependSql('SELECT COUNT(*) as count FROM (')->appendSql(') as baseQuery');
        } else {
            $this->writeOrderSection();
            $this->writeLimitSection();
        }

        return $this->_stmt;
    }


    public function executeRemoteJoinedReadQuery($tableName, $forCount = false)
    {
        $source = $this->_query->getSource();
        $primaryAdapterHash = $source->getAdapterHash();

        $outFields = [];
        $aggregateFields = [];

        foreach ($source->getAllDereferencedFields() as $field) {
            $qName = $field->getQualifiedName();

            if ($field instanceof opal\query\IAggregateField) {
                $field->setLogicalAlias($field->getSource()->getAlias() . '.' . $field->getAlias());
                $aggregateFields[$qName] = $field;
            } else {
                $field->setLogicalAlias($qName);
                $outFields[$qName] = $this->defineField($field, $qName);
            }
        }


        // Joins & fields
        $remoteJoins = [];
        $localJoins = [];
        $joinSql = null;

        foreach ($this->_query->getJoins() as $joinSourceAlias => $join) {
            $joinSource = $join->getSource();
            $hash = $joinSource->getAdapterHash();

            // Test to see if this is a remote join
            if ($hash != $primaryAdapterHash || $join->referencesSourceAliases(array_keys($remoteJoins))) {
                $remoteJoins[$joinSourceAlias] = $join;
                continue;
            }

            $localJoins[$joinSourceAlias] = $join;

            foreach ($joinSource->getAllDereferencedFields() as $field) {
                $qName = $field->getQualifiedName();

                if ($field instanceof opal\query\IAggregateField) {
                    $aggregateFields[$qName] = $field;
                } else {
                    $outFields[$qName] = $this->defineField($field, $qName);
                }
            }

            $jExec = QueryExecutor::factory($this->_adapter, $join);
            $jExec->_isMultiDb = $this->_isMultiDb;
            $joinSql .= "\n" . $jExec->buildJoin($this->_stmt);
        }


        $this->_stmt->appendSql(
            'SELECT' . "\n" . '  ' . implode(',' . "\n" . '  ', $outFields) . "\n" .
            'FROM ' . $this->_adapter->quoteIdentifier($tableName) . ' ' .
            'AS ' . $this->_adapter->quoteTableAliasDefinition($source->getAlias()) .
            $joinSql
        );



        // Filter results
        if ($this->_query instanceof opal\query\IWhereClauseQuery && $this->_query->hasWhereClauses()) {
            $clauseList = $this->_query->getWhereClauseList();

            /*
             * We can filter down the results even though the join is processed locally -
             * we just need to only add where clauses that are local to the primary source
             * The main algorithm for finding those clauses is in the clause list class
             */
            if (null !== ($joinClauseList = $clauseList->getClausesFor($source))) {
                $this->writeWhereClauseList($joinClauseList);
            }
        }


        /*
         * The following is a means of tentatively ordering and limiting the result set
         * used to create the join. It probably needs a bit of TLC :)
         */
        if (!$forCount) {
            $isPrimaryOrder = true;

            if ($this->_query instanceof opal\query\IOrderableQuery) {
                $isPrimaryOrder = $this->_query->isPrimaryOrderSource();
            }

            if (empty($aggregateFields) && $isPrimaryOrder) {
                $this->writeOrderSection(false, true);
                $this->writeLimitSection();
            }
        }

        $arrayManipulator = new opal\native\ArrayManipulator($source, $this->_stmt->executeRead()->toArray(), true);
        return $arrayManipulator->applyRemoteJoinQuery($this->_query, $localJoins, $remoteJoins, $forCount);
    }



    // Insert
    public function executeInsertQuery($tableName)
    {
        $this->_stmt->appendSql('INSERT INTO ' . $this->_adapter->quoteIdentifier($tableName));

        $fields = [];
        $values = [];
        $duplicates = [];

        foreach ($this->_query->getPreparedRow() as $field => $value) {
            $fields[] = $fieldString = $this->_adapter->quoteIdentifier($field);
            $values[] = ':' . $field;
            $duplicates[] = $fieldString . '=VALUES(' . $fieldString . ')';

            if (is_array($value)) {
                if (count($value) == 1) {
                    // This is a total hack - you need to trace the real problem with multi-value keys
                    $value = array_shift($value);
                } else {
                    dd($value, $field, 'You need to trace back multi key primary field retention');
                }
            }

            $this->_stmt->bind($field, $value);
        }

        $this->_stmt->appendSql(' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')');

        if ($this->_query->shouldReplace()) {
            $this->_stmt->appendSql("\n" . 'ON DUPLICATE KEY UPDATE ' . implode(', ', $duplicates));
        } elseif ($this->_query->ifNotExists()) {
            $this->_stmt->appendSql(' ON DUPLICATE KEY UPDATE ' . $fields[0] . '=' . $fields[0]);
        }

        $this->_stmt->executeWrite();

        return $this->_adapter->getLastInsertId();
    }

    // Batch insert
    public function executeBatchInsertQuery($tableName)
    {
        $this->_stmt->appendSql('INSERT INTO ' . $this->_adapter->quoteIdentifier($tableName));

        $rows = [];
        $fields = [];
        $fieldList = $this->_query->getDereferencedFields();
        $duplicates = [];

        foreach ($fieldList as $field) {
            $fields[] = $fieldString = $this->_adapter->quoteIdentifier($field);
            $duplicates[] = $fieldString . '=VALUES(' . $fieldString . ')';
        }

        foreach ($this->_query->getPreparedRows() as $row) {
            $current = [];

            foreach ($fieldList as $key) {
                $value = null;

                if (isset($row[$key])) {
                    $value = $row[$key];
                }

                $current[$key] = ':' . $this->_stmt->autoBind($value);
            }

            $rows[] = '(' . implode(',', $current) . ')';
        }

        $this->_stmt->appendSql(' (' . implode(',', $fields) . ') VALUES ' . implode(', ', $rows));

        if ($this->_query->shouldReplace()) {
            $this->_stmt->appendSql("\n" . 'ON DUPLICATE KEY UPDATE ' . implode(', ', $duplicates));
        } elseif ($this->_query->ifNotExists()) {
            $this->_stmt->appendSql(' ON DUPLICATE KEY UPDATE ' . $fields[0] . '=' . $fields[0]);
        }

        $this->_stmt->executeWrite();

        return count($rows);
    }

    // Update
    public function executeUpdateQuery($tableName)
    {
        if (!$this->_query instanceof opal\query\IUpdateQuery) {
            throw Exceptional::UnexpectedValue(
                'Executor query is not an update'
            );
        }

        $this->_stmt->appendSql(
            'UPDATE ' . $this->_adapter->quoteIdentifier($tableName) .
            //' AS '.$this->_adapter->quoteTableAliasDefinition($this->_query->getSource()->getAlias()).
            ' SET'
        );

        $values = [];

        foreach ($this->_query->getPreparedValues() as $field => $value) {
            if ($value instanceof opal\query\IExpression) {
                $values[] = $this->_adapter->quoteIdentifier($field) . ' = ' . $this->defineExpression($value);
            } else {
                $values[] = $this->_adapter->quoteIdentifier($field) . ' = :' . $this->_stmt->autoBind($value);
            }
        }

        $this->_stmt->appendSql("\n  " . implode(',' . "\n" . '  ', $values));
        $this->writeWhereClauseSection(null, true);

        if ($this->_adapter->supports(opal\rdbms\adapter\Base::UPDATE_LIMIT)) {
            $this->writeOrderSection(true);
            $this->writeLimitSection(true);
        }

        return $this->_stmt->executeWrite();
    }

    // Delete
    public function executeDeleteQuery($tableName)
    {
        if (!$this->_query instanceof opal\query\IDeleteQuery) {
            throw Exceptional::UnexpectedValue(
                'Executor query is not a delete'
            );
        }

        $this->_stmt->appendSql('DELETE FROM ' . $this->_adapter->quoteIdentifier($tableName));
        $this->writeWhereClauseSection(null, true);

        if ($this->_adapter->supports(opal\rdbms\adapter\Base::DELETE_LIMIT)) {
            $this->writeOrderSection(true);
            $this->writeLimitSection(true);
        }

        return $this->_stmt->executeWrite();
    }


    // Remote data
    public function fetchRemoteJoinData($tableName, array $rows)
    {
        if (!$this->_query instanceof opal\query\IJoinQuery) {
            throw Exceptional::UnexpectedValue(
                'Executor query is not a join'
            );
        }

        $sources = [];
        $outFields = [];

        $source = $this->_query->getSource();
        $sources[$source->getUniqueId()] = $source;
        $parentSource = $this->_query->getParentSource();
        $sources[$parentSource->getUniqueId()] = $parentSource;

        foreach ($this->_query->getSource()->getAllDereferencedFields() as $field) {
            if ($field instanceof opal\query\IAggregateField) {
                continue;
            }

            $fieldAlias = $field->getQualifiedName();
            $field->setLogicalAlias($fieldAlias);
            $outFields[] = $this->defineField($field, $fieldAlias);
        }

        $this->_stmt->appendSql(
            'SELECT' . "\n" . '  ' . implode(',' . "\n" . '  ', $outFields) . "\n" .
            'FROM ' . $this->_adapter->quoteIdentifier($tableName) . ' ' .
            'AS ' . $this->_adapter->quoteTableAliasDefinition($source->getAlias())
        );

        $clauses = $this->_query->getJoinClauseList();

        if (!$clauses->isEmpty() && $clauses->isLocalTo($sources)) {
            $this->writeWhereClauseList($clauses, $rows);
        }

        return $this->_stmt->executeRead();
    }

    public function fetchAttachmentData($tableName, array $rows)
    {
        if (!$this->_query instanceof opal\query\IAttachQuery) {
            throw Exceptional::UnexpectedValue(
                'Executor query is not an attachment'
            );
        }

        $outFields = [];
        $sources = [];

        $source = $this->_query->getSource();
        $sources[$source->getUniqueId()] = $source;
        $sourceManager = $this->_query->getSourceManager();

        foreach ($source->getAllDereferencedFields() as $field) {
            if ($field instanceof opal\query\IAggregateField) {
                continue;
            }

            $fieldAlias = $field->getQualifiedName();
            $field->setLogicalAlias($fieldAlias);
            $outFields[] = $this->defineField($field, $fieldAlias);
        }

        $joinSql = null;
        $joinsApplied = false;

        if ($sourceManager->canQueryLocally() && $this->_query instanceof opal\query\IJoinProviderQuery) {
            $this->_isMultiDb = $sourceManager->countSourceAdapters() > 1;

            foreach ($this->_query->getJoins() as $joinSourceAlias => $join) {
                $joinSource = $join->getSource();
                $sources[$joinSource->getUniqueId()] = $joinSource;

                $jExec = QueryExecutor::factory($this->_adapter, $join);
                $jExec->_isMultiDb = $this->_isMultiDb;
                $joinSql .= "\n" . $jExec->buildJoin($this->_stmt);

                foreach ($joinSource->getAllDereferencedFields() as $field) {
                    if (!$field instanceof opal\query\IAggregateField) {
                        $outFields[] = $this->defineField($field, $field->getQualifiedName());
                    }
                }
            }

            $joinsApplied = true;
        }


        $this->_stmt->appendSql(
            'SELECT' . "\n" . '  ' . implode(',' . "\n" . '  ', array_unique($outFields)) . "\n" .
            'FROM ' . $this->_adapter->quoteIdentifier($tableName) . ' ' .
            'AS ' . $this->_adapter->quoteTableAliasDefinition($source->getAlias()) .
            $joinSql
        );


        $joinClauses = $this->_query->getJoinClauseList();
        $canUseJoinClauses = !$joinClauses->isEmpty() && $joinClauses->isLocalTo($sources);

        if ($this->_query instanceof opal\query\IWhereClauseQuery) {
            $whereClauses = $this->_query->getWhereClauseList();
            $canUseWhereClauses = $clausesApplied = !$whereClauses->isEmpty() && $whereClauses->isLocalTo($sources);
        } else {
            $whereClauses = null;
            $canUseWhereClauses = $clausesApplied = false;
        }

        if ($canUseJoinClauses) {
            if ($canUseWhereClauses) {
                $clauses = new opal\query\clause\ListBase($this->_query->getParentQuery());
                $clauses->_addClause($joinClauses);
                $clauses->_addClause($whereClauses);
            } else {
                $clauses = $joinClauses;
            }
        } elseif ($canUseWhereClauses) {
            $clauses = $whereClauses;
        } else {
            $clauses = null;
        }


        // Filter clause for attachment clauses
        if (!$canUseJoinClauses && !$joinClauses->isEmpty()) {
            $useJoins = true;
            $manualJoins = clone $joinClauses;
            $manualJoins->clear();

            foreach ($joinClauses->toArray() as $clause) {
                if (!$clause instanceof opal\query\IClause) {
                    $useJoins = false;
                    break;
                }

                $clause = clone $clause;
                $field = $clause->getValue();
                $fieldName = $field->getQualifiedName();
                $operator = $clause->getOperator();

                if (opal\query\clause\Clause::normalizeOperator($operator) != '=') {
                    $useJoins = false;
                    break;
                }

                $value = [];

                foreach ($rows as $row) {
                    if (!array_key_exists($fieldName, $row)) {
                        $useJoins = false;
                        break 2;
                    }

                    $value[$row[$fieldName]] = $row[$fieldName];
                }

                $newOperator = 'in';

                if (opal\query\clause\Clause::isNegatedOperator($operator)) {
                    $newOperator = '!in';
                }

                $clause->setOperator($newOperator);
                $clause->setValue($value);
                $manualJoins->_addClause($clause);
            }

            if ($useJoins) {
                if ($clauses) {
                    $clauses->_addClause($manualJoins);
                } else {
                    $clauses = $manualJoins;
                }
            }
        }


        if ($clauses) {
            $this->writeWhereClauseList($clauses, $rows);
        }

        // TODO: check this is viable :)
        $this->writeOrderSection();


        $manipulator = new opal\native\ArrayManipulator($source, $this->_stmt->executeRead()->toArray(), true);
        return $manipulator->applyAttachmentDataQuery($this->_query, $joinsApplied, $clausesApplied);
    }



    // Correlations
    public function buildCorrelation(IStatement $stmt)
    {
        if (!$this->_query instanceof opal\query\ICorrelationQuery) {
            throw Exceptional::UnexpectedValue(
                'Executor query is not a correlation'
            );
        }

        $this->_stmt->setKeyIndex($stmt->getKeyIndex());

        $source = $this->_query->getSource();
        $outFields = [];

        $supportsProcessors = $source->getAdapter()->supportsQueryFeature(
            opal\query\IQueryFeatures::VALUE_PROCESSOR
        );


        // Fields
        $fieldAlias = $this->_query->getFieldAlias();
        $field = $source->getFieldByAlias($fieldAlias);

        if (!$field) {
            throw Exceptional::Runtime(
                'Correlation field not found.. this shouldn\'t happen!',
                null,
                [$fieldAlias, $source]
            );
        }

        $outFields[/*$fieldAlias*/ ] = $this->defineField($field, $fieldAlias);


        // Joins
        $joinSql = null;

        foreach ($this->_query->getJoins() as $joinSourceAlias => $join) {
            $joinSource = $join->getSource();
            $hash = $joinSource->getAdapterHash();

            // TODO: make sure it's not a remote join

            $exec = self::factory($this->_adapter, $join);
            $joinSql .= "\n" . $exec->buildJoin($this->_stmt);
        }

        $tableAdapter = $source->getAdapter()->getDelegateQueryAdapter();
        $tableName = $this->_adapter->quoteIdentifier($tableAdapter->getName());

        if ($this->_isMultiDb) {
            $tableName = $this->_adapter->quoteIdentifier($tableAdapter->getDatabaseName()) . '.' . $tableName;
        }

        // SQL
        $this->_stmt->appendSql(
            'SELECT' . "\n" . '  ' . implode(',' . "\n" . '  ', $outFields) . "\n" .
            'FROM ' . $tableName . ' ' .
            'AS ' . $this->_adapter->quoteTableAliasDefinition($source->getAlias()) .
            $joinSql
        );


        // Clauses
        $joinClauses = $this->_query->getJoinClauseList();
        $whereClauses = $this->_query->getWhereClauseList();

        if (!$joinClauses->isEmpty()) {
            if (!$whereClauses->isEmpty()) {
                $clauses = new opal\query\clause\ListBase($this->_query);
                $clauses->_addClause($joinClauses);
                $clauses->_addClause($whereClauses);
            } else {
                $clauses = $joinClauses;
            }
        } else {
            $clauses = $whereClauses;
        }

        $this->writeWhereClauseList($clauses);
        $this->writeGroupSection();
        $this->writeOrderSection();
        $this->writeLimitSection();

        $stmt->importBindings($this->_stmt);
        $stmt->setKeyIndex($this->_stmt->getKeyIndex());

        return $this->_stmt->getSql();
    }



    // Join
    public function buildJoin(IStatement $stmt)
    {
        if (!$this->_query instanceof opal\query\IJoinQuery) {
            throw Exceptional::UnexpectedValue(
                'Executor query is not a join'
            );
        }

        $this->_stmt->setKeyIndex($stmt->getKeyIndex());

        switch ($this->_query->getType()) {
            case opal\query\IJoinQuery::INNER:
                $this->_stmt->appendSql('INNER');
                break;

            case opal\query\IJoinQuery::LEFT:
                $this->_stmt->appendSql('LEFT');
                break;

            case opal\query\IJoinQuery::RIGHT:
                $this->_stmt->appendSql('RIGHT');
                break;
        }

        $source = $this->_query->getSource();

        if ($source->isDerived()) {
            $query = $source->getAdapter()->getDerivationQuery();
            $qExec = QueryExecutor::factory($this->_adapter, $query);
            $qExec->_isMultiDb = $this->_isMultiDb;
            $tableName = $query->getSource()->getAdapter()->getDelegateQueryAdapter()->getName();

            if ($query instanceof opal\query\ISelectQuery) {
                $qExec->buildLocalReadQuery($tableName, false);
            } elseif ($query instanceof opal\query\IUnionQuery) {
                $qExec->buildUnionQuery($tableName, false);
            } else {
                throw Exceptional::{'df/opal/query/Logic'}(
                    'Don\'t know how to derive from query type: ' . $query->getQueryType()
                );
            }

            $statement = $qExec->getStatement();
            $this->_stmt->importBindings($statement);
            $this->_stmt->appendSql(' JOIN (' . "\n" . '    ' . str_replace("\n", "\n    ", $statement->getSql()) . "\n" . ') ');
        } else {
            $adapter = $source->getAdapter()->getDelegateQueryAdapter();
            $tableName = $this->_adapter->quoteIdentifier($adapter->getName());

            if ($this->_isMultiDb) {
                $tableName = $this->_adapter->quoteIdentifier($adapter->getDatabaseName()) . '.' . $tableName;
            }

            $this->_stmt->appendSql(
                ' JOIN ' . $tableName
            );
        }



        $this->_stmt->appendSql(
            ' AS ' . $this->_adapter->quoteTableAliasDefinition($this->_query->getSourceAlias())
        );

        $onClauses = $this->_query->getJoinClauseList();
        $whereClauses = $this->_query->getWhereClauseList();
        $onClausesEmpty = $onClauses->isEmpty();
        $whereClausesEmpty = $whereClauses->isEmpty();
        $clauses = null;

        if (!$onClausesEmpty && !$whereClausesEmpty) {
            $clauses = new opal\query\clause\ListBase($this->_query);
            $clauses->_addClause($onClauses);
            $clauses->_addClause($whereClauses);
        } elseif (!$onClausesEmpty) {
            $clauses = $onClauses;
        } elseif (!$whereClausesEmpty) {
            $clauses = $whereClauses;
        }

        if ($clauses) {
            $this->writeJoinClauseList($clauses);
        }

        $stmt->importBindings($this->_stmt);
        $stmt->setKeyIndex($this->_stmt->getKeyIndex());

        return $this->_stmt->getSql();
    }



    // Fields
    public function defineField(opal\query\IField $field, $alias = null)
    {
        /*
         * This method is used in many places to get a string representation of a
         * query field. If $defineAlias is true, it is suffixed with AS <alias> and
         * denotes a field in the initial SELECT statement.
         */
        $defineAlias = true;
        $output = null;

        if ($alias === false) {
            $defineAlias = false;
        }

        // Wildcard
        if ($field instanceof opal\query\IWildcardField) {
            $output = $this->_adapter->quoteTableAliasReference($field->getSourceAlias()) . '.*';
            $defineAlias = false;


            // Aggregate
        } elseif ($field instanceof opal\query\IAggregateField) {
            $targetField = $field->getTargetField();

            if ($targetField instanceof opal\query\IWildcardField) {
                $targetFieldString = '*';
            } else {
                $targetFieldString = $this->defineField($field->getTargetField(), false);
            }

            if ($field->isDistinct()) {
                $targetFieldString = 'DISTINCT ' . $targetFieldString;
            }

            switch ($field->getType()) {
                case opal\query\field\Aggregate::TYPE_HAS:
                    $output = 'COUNT(' . $targetFieldString . ')';
                    break;

                default:
                    $output = $field->getTypeName() . '(' . $targetFieldString . ')';
                    break;
            }


            // Expression
        } elseif ($field instanceof opal\query\IExpressionField) {
            $expression = $field->getExpression();

            if ($expression) {
                Glitch::incomplete($expression);
            } else {
                $output = 'NULL';
            }


            // Intrinsic
        } elseif ($field instanceof opal\query\IIntrinsicField) {
            // Intrinsic
            $output =
                $this->_adapter->quoteTableAliasReference($field->getSourceAlias()) . '.' .
                $this->_adapter->quoteFieldAliasReference($field->getName());


            // Virtual
        } elseif ($field instanceof opal\query\IVirtualField) {
            $deref = $field->dereference();

            if (count($deref) == 1) {
                return $this->defineField($deref[0], $alias);
            }

            throw Exceptional::InvalidArgument(
                'Virtual fields can not be used directly'
            );


            // Raw
        } elseif ($field instanceof opal\query\IRawField) {
            $output = $field->getExpression();

            // Correlation
        } elseif ($field instanceof opal\query\ICorrelationField) {
            $exec = self::factory($this->_adapter, $field->getCorrelationQuery());
            $sql = $exec->buildCorrelation($this->_stmt);
            $output = '(' . "\n" . '    ' . str_replace("\n", "\n    ", $sql) . "\n" . '  )';


            // Search
        } elseif ($field instanceof opal\query\ISearchController) {
            $output = [];

            foreach ($field->generateCaseList() as $case) {
                $output[] = 'CASE WHEN ' . $this->defineClause($case['clause']) . ' THEN ' . $case['weight'] . ' ELSE 0 END';
            }

            if (empty($output)) {
                $output = '0';
            } else {
                $max = $field->getMaxScore();
                $output = 'LEAST((' . implode(' + ', $output) . ') / ' . $max . ', 1)';
            }
        } else {
            throw Exceptional::UnexpectedValue(
                'Field type ' . get_class($field) . ' is not currently supported'
            );
        }

        if ($defineAlias) {
            if ($alias === null) {
                $alias = $field->getAlias();
            }

            $output .= ' AS ' . $this->_adapter->quoteFieldAliasDefinition($alias);
        }

        return $output;
    }

    public function defineFieldReference(opal\query\IField $field, $allowAlias = false, $forUpdateOrDelete = false)
    {
        $isDiscreetAggregate = $field instanceof opal\query\IAggregateField
                            && $field->hasDiscreetAlias();

        $deepNest = false;

        if (!$isDiscreetAggregate
        && $this->_query instanceof opal\query\IParentQueryAware
        && $this->_query->isSourceDeepNested($field->getSource())) {
            $deepNest = true;

            if ($allowAlias === IAlias::DEEP_DEFINITION) {
                $allowAlias = IAlias::DEFINITION;
            }
        }

        if (!$deepNest && $allowAlias === IAlias::DEEP_DEFINITION) {
            $allowAlias = IAlias::NONE;
        }

        if (($isDiscreetAggregate && !$allowAlias) || $field instanceof opal\query\IRawField) {
            // Reference an aggregate by alias
            if ($field->isOutputField()) {
                return $this->_adapter->quoteFieldAliasReference($field->getQualifiedName());
            } elseif ($field instanceof opal\query\IRawField) {
                return $field->getExpression();
            } else {
                // TODO: Add getExpression() to aggregates
                return $field->getQualifiedName();
            }
        } elseif ($forUpdateOrDelete) {
            /*
             * If used on an aggregate field or for update or delete, the name must
             * be used on some sql servers
             */
            return $this->_adapter->quoteIdentifier($field->getName());
        } elseif ($allowAlias && ($alias = $field->getLogicalAlias())) {
            // Defined in a field list
            if ($allowAlias === IAlias::DEFINITION) {
                return $this->_adapter->quoteFieldAliasDefinition($alias);
            } else {
                return $this->_adapter->quoteFieldAliasReference($alias);
            }
        } elseif ($field instanceof opal\query\ICorrelationField) {
            return $this->_adapter->quoteFieldAliasReference($field->getLogicalAlias());
        } else {
            return $this->_adapter->quoteTableAliasReference($field->getSourceAlias()) . '.' .
                    $this->_adapter->quoteFieldAliasReference($field->getName());
        }
    }


    // Clauses
    public function writeJoinClauseSection()
    {
        if (!$this->_query instanceof opal\query\IJoinClauseFactory || !$this->_query->hasJoinClauses()) {
            return $this;
        }

        return $this->writeJoinClauseList($this->_query->getJoinClauseList());
    }

    public function writeJoinClauseList(opal\query\IClauseList $clauses)
    {
        if ($clauses->isEmpty()) {
            return $this;
        }

        $clauseString = $this->defineClauseList($clauses, null, IAlias::DEEP_DEFINITION);

        if (!empty($clauseString)) {
            $this->_stmt->appendSql("\n" . '  ON ' . $clauseString);
        }

        return $this;
    }

    public function writeWhereClauseSection(array $remoteJoinData = null, $forUpdateOrDelete = false)
    {
        if (!$this->_query instanceof opal\query\IWhereClauseQuery || !$this->_query->hasWhereClauses()) {
            return $this;
        }

        return $this->writeWhereClauseList($this->_query->getWhereClauseList(), $remoteJoinData, $forUpdateOrDelete);
    }

    public function writeWhereClauseList(opal\query\IClauseList $clauses, array $remoteJoinData = null, $forUpdateOrDelete = false)
    {
        if ($clauses->isEmpty()) {
            return $this;
        }

        $clauseString = $this->defineClauseList($clauses, $remoteJoinData, false, $forUpdateOrDelete);

        if (!empty($clauseString)) {
            $this->_stmt->appendSql("\n" . 'WHERE ' . $clauseString);
        }

        return $this;
    }

    public function writeHavingClauseSection()
    {
        if (!$this->_query instanceof opal\query\IHavingClauseQuery || !$this->_query->hasHavingClauses()) {
            return $this;
        }

        return $this->writeHavingClauseList($this->_query->getHavingClauseList());
    }

    public function writeHavingClauseList(opal\query\IClauseList $clauses)
    {
        if ($clauses->isEmpty()) {
            return $this;
        }

        $clauseString = $this->defineClauseList($clauses, null, true);

        if (!empty($clauseString)) {
            $this->_stmt->appendSql("\n" . 'HAVING ' . $clauseString);
        }

        return $this;
    }

    public function defineClauseList(opal\query\IClauseList $list, array $remoteJoinData = null, $allowAlias = false, $forUpdateOrDelete = false)
    {
        $output = '';

        foreach ($list->toArray() as $clause) {
            if ($clause instanceof opal\query\IClause) {
                $clauseString = $this->defineClause(
                    $clause,
                    $remoteJoinData,
                    $allowAlias,
                    $forUpdateOrDelete
                );
            } elseif ($clause instanceof opal\query\IClauseList) {
                $clauseString = $this->defineClauseList(
                    $clause,
                    $remoteJoinData,
                    $allowAlias,
                    $forUpdateOrDelete
                );
            }

            if (empty($clauseString)) {
                continue;
            }

            if (!empty($output)) {
                if ($clause->isOr()) {
                    $separator = ' OR ';
                } else {
                    $separator = ' AND ';
                }

                $clauseString = $separator . $clauseString;
            }

            $output .= $clauseString;
        }

        if (empty($output)) {
            return null;
        }

        return '(' . $output . ')';
    }

    public function defineClause(opal\query\IClause $clause, array $remoteJoinData = null, $allowAlias = false, $forUpdateOrDelete = false)
    {
        $field = $clause->getField();
        $operator = $clause->getOperator();
        $value = $clause->getPreparedValue();
        $fieldString = $this->defineFieldReference($field, $allowAlias, $forUpdateOrDelete);

        if ($remoteJoinData !== null) {
            /*
             * If we're defining a clause for a remote join we will be comparing
             * a full dataset, but with an operator defined for a single value
             */

            if ($value instanceof opal\query\ICorrelationQuery) {
                $value = clone $value;

                $correlationSource = $value->getCorrelationSource();
                $correlationSourceAlias = $correlationSource->getAlias();

                foreach ($value->getCorrelatedClauses($correlationSource) as $correlationClause) {
                    $field = $correlationClause->getField();

                    if ($field->getSourceAlias() == $correlationSourceAlias) {
                        Glitch::incomplete([$field, 'What exactly are we supposed to do with left hand side clause correlations???!?!?!?!?']);
                    }

                    $correlationValue = $correlationClause->getValue();

                    if ($correlationValue instanceof opal\query\IField
                    && $correlationValue->getSourceAlias() == $correlationSourceAlias) {
                        $qName = $correlationValue->getQualifiedName();
                        $valueData = $this->_getClauseValueForRemoteJoinData($correlationClause, $remoteJoinData, $qName, $operator);

                        $correlationClause->setOperator($operator);
                        $correlationClause->setValue($valueData);
                    }
                }
            } elseif ($value instanceof opal\query\IField) {
                $qName = $value->getQualifiedName();
                $value = $this->_getClauseValueForRemoteJoinData($clause, $remoteJoinData, $qName, $operator);
            }
        }

        if ($value instanceof opal\query\ICorrelationQuery) {
            // Subqueries need to be handled separately
            return $this->defineClauseCorrelation($field, $fieldString, $operator, $value, $allowAlias);
        } else {
            // Define a standard expression
            return $this->defineClauseExpression($field, $fieldString, $operator, $value, $allowAlias);
        }
    }

    protected function _getClauseValueForRemoteJoinData(opal\query\IClause $clause, array $remoteJoinData, $qName, &$operator)
    {
        $listData = [];

        foreach ($remoteJoinData as $row) {
            if (isset($row[$qName])) {
                $listData[] = $row[$qName];
            } else {
                $listData[] = null;
            }
        }

        switch ($operator) {
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_EQ_NULL:
            case opal\query\clause\Clause::OP_IN:
            case opal\query\clause\Clause::OP_LIKE:
            case opal\query\clause\Clause::OP_CONTAINS:
            case opal\query\clause\Clause::OP_BEGINS:
            case opal\query\clause\Clause::OP_ENDS:
            case opal\query\clause\Clause::OP_MATCHES:
                // Test using IN operator on data set
                $operator = opal\query\clause\Clause::OP_IN;
                return array_unique($listData);

            case opal\query\clause\Clause::OP_NEQ:
            case opal\query\clause\Clause::OP_NEQ_NULL:
            case opal\query\clause\Clause::OP_NOT_IN:
            case opal\query\clause\Clause::OP_NOT_LIKE:
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
            case opal\query\clause\Clause::OP_NOT_BEGINS:
            case opal\query\clause\Clause::OP_NOT_ENDS:
            case opal\query\clause\Clause::OP_NOT_MATCHES:
                // Test using NOT IN operator on data set
                $operator = opal\query\clause\Clause::OP_NOT_IN;
                return array_unique($listData);

            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
                // We only need to test against the lowest value
                return min($listData);

            case opal\query\clause\Clause::OP_LT:
            case opal\query\clause\Clause::OP_LTE:
                // We only need to test against the highest value
                return max($listData);

            default:
                throw Exceptional::{'df/opal/query/Operator'}(
                    'Operator ' . $operator . ' cannot be used for a remote join'
                );
        }
    }

    public function defineClauseCorrelation(opal\query\IField $field, $fieldString, $operator, opal\query\ICorrelationQuery $correlation, $allowAlias = false)
    {
        $source = $correlation->getSource();
        $isSourceLocal = $source->getAdapterHash() == $this->_adapter->getDsnHash();
        $hasRemoteSources = $correlation->getSourceManager()->countSourceAdapters() > 1;
        $isRegexp = false;

        switch ($operator) {
            case opal\query\clause\Clause::OP_CONTAINS:
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
            case opal\query\clause\Clause::OP_BEGINS:
            case opal\query\clause\Clause::OP_NOT_BEGINS:
            case opal\query\clause\Clause::OP_ENDS:
            case opal\query\clause\Clause::OP_NOT_ENDS:
            case opal\query\clause\Clause::OP_MATCHES:
            case opal\query\clause\Clause::OP_NOT_MATCHES:
                // Regexp clauses cannot be done inline
                $isRegexp = true;
        }

        if (!$isRegexp && $isSourceLocal && !$hasRemoteSources) {
            // The subquery can be put directly into the parent query
            return $this->defineClauseLocalCorrelation($field, $fieldString, $operator, $correlation);
        } else {
            throw Exceptional::Runtime(
                'Unable to use remote correlations as clauses'
            );
        }
    }

    public function defineClauseLocalCorrelation(opal\query\IField $field, $fieldString, $operator, opal\query\ICorrelationQuery $correlation)
    {
        $limit = $correlation->getLimit();

        switch ($operator) {
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_EQ_NULL:
            case opal\query\clause\Clause::OP_NEQ:
            case opal\query\clause\Clause::OP_NEQ_NULL:
            case opal\query\clause\Clause::OP_LIKE:
            case opal\query\clause\Clause::OP_NOT_LIKE:
                // There can only be one value for this operator..
                $correlation->limit(1);
                break;

            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
            case opal\query\clause\Clause::OP_LT:
            case opal\query\clause\Clause::OP_LTE:
                // This ensures we check against all possible values
                $operator .= ' ALL';
                break;
        }

        $exec = self::factory($this->_adapter, $correlation);
        $sql = $exec->buildCorrelation($this->_stmt);

        // `myField` = (SELECT FROM ...)
        return $fieldString . ' ' . strtoupper($operator) . ' (' . "\n  " . str_replace("\n", "\n  ", $sql) . "\n" . ')';
    }

    public function defineClauseExpression(opal\query\IField $field, $fieldString, $operator, $value, $allowAlias = false)
    {
        switch ($operator) {
            // = | <=> | != | <>
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_EQ_NULL:
            case opal\query\clause\Clause::OP_NEQ:
            case opal\query\clause\Clause::OP_NEQ_NULL:
                if ($value === null) {
                    if ($operator == '=') {
                        return $fieldString . ' IS NULL';
                    } else {
                        return $fieldString . ' IS NOT NULL';
                    }
                }

                return $fieldString . ' ' . $operator . ' ' . $this->normalizeScalarClauseValue($value, $allowAlias);

                // > | >= | < | <=
            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
            case opal\query\clause\Clause::OP_LT:
            case opal\query\clause\Clause::OP_LTE:
                return $fieldString . ' ' . $operator . ' ' . $this->normalizeScalarClauseValue($value, $allowAlias);

                // <NOT> IN()
            case opal\query\clause\Clause::OP_IN:
            case opal\query\clause\Clause::OP_NOT_IN:
                $not = $operator == opal\query\clause\Clause::OP_NOT_IN;

                if (empty($value)) {
                    return '1 ' . ($not ? null : '!') . '= 1';
                }

                $hasNull = false;

                if (in_array(null, $value)) {
                    $value = array_filter($value, function ($a) {
                        return $a !== null;
                    });
                    $hasNull = true;
                }

                $output = $fieldString . ($not ? ' NOT' : null) . ' IN (' . implode(',', $this->normalizeArrayClauseValue($value, $allowAlias)) . ')';

                if ($hasNull) {
                    $output = '(' . $output . ' OR ' . $fieldString . ' IS' . ($not ? ' NOT' : null) . ' NULL)';
                }

                return $output;

                // BETWEEN()
            case opal\query\clause\Clause::OP_BETWEEN:
                $value = $this->normalizeArrayClauseValue($value, $allowAlias);
                return $fieldString . ' BETWEEN ' . array_shift($value) . ' AND ' . array_shift($value);

                // NOT BETWEEN()
            case opal\query\clause\Clause::OP_NOT_BETWEEN:
                $value = $this->normalizeArrayClauseValue($value, $allowAlias);
                return $fieldString . ' NOT BETWEEN ' . array_shift($value) . ' AND ' . array_shift($value);

                // LIKE
            case opal\query\clause\Clause::OP_LIKE:
                return $fieldString . ' LIKE ' . $this->normalizeScalarClauseValue(
                    str_replace(['?', '*'], ['_', '%'], $value),
                    $allowAlias
                );

                // NOT LIKE
            case opal\query\clause\Clause::OP_NOT_LIKE:
                return $fieldString . ' NOT LIKE ' . $this->normalizeScalarClauseValue(
                    str_replace(['?', '*'], ['_', '%'], $value),
                    $allowAlias
                );

                // LIKE %<value>%
            case opal\query\clause\Clause::OP_CONTAINS:
                return $fieldString . ' LIKE ' . $this->normalizeScalarClauseValue(
                    '%' . str_replace(['_', '%'], ['\_', '\%'], $value) . '%',
                    $allowAlias
                );

                // NOT LIKE %<value>%
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
                return $fieldString . ' NOT LIKE ' . $this->normalizeScalarClauseValue(
                    '%' . str_replace(['_', '%'], ['\_', '\%'], $value) . '%',
                    $allowAlias
                );

                // LIKE <value>%
            case opal\query\clause\Clause::OP_BEGINS:
                return $fieldString . ' LIKE ' . $this->normalizeScalarClauseValue(
                    str_replace(['_', '%'], ['\_', '\%'], $value) . '%',
                    $allowAlias
                );

                // NOT LIKE <value>%
            case opal\query\clause\Clause::OP_NOT_BEGINS:
                return $fieldString . ' NOT LIKE ' . $this->normalizeScalarClauseValue(
                    str_replace(['_', '%'], ['\_', '\%'], $value) . '%',
                    $allowAlias
                );

                // LIKE %<value>
            case opal\query\clause\Clause::OP_ENDS:
                return $fieldString . ' LIKE ' . $this->normalizeScalarClauseValue(
                    '%' . str_replace(['_', '%'], ['\_', '\%'], $value),
                    $allowAlias
                );

                // NOT LIKE %<value>
            case opal\query\clause\Clause::OP_NOT_ENDS:
                return $fieldString . ' NOT LIKE ' . $this->normalizeScalarClauseValue(
                    '%' . str_replace(['_', '%'], ['\_', '\%'], $value),
                    $allowAlias
                );

                // MATCHES
            case opal\query\clause\Clause::OP_MATCHES:
                return $fieldString . ' LIKE ' . $this->normalizeScalarClauseValue(
                    '%' . str_replace(['?', '*'], ['_', '%'], $value) . '%',
                    $allowAlias
                );

                // NOT MATCHES
            case opal\query\clause\Clause::OP_NOT_MATCHES:
                return $fieldString . ' NOT LIKE ' . $this->normalizeScalarClauseValue(
                    '%' . str_replace(['?', '*'], ['_', '%'], $value) . '%',
                    $allowAlias
                );


            default:
                throw Exceptional::{'df/opal/query/Operator'}(
                    'Operator ' . $operator . ' is not recognized'
                );
        }
    }

    public function normalizeArrayClauseValue($value, $allowAlias = false)
    {
        /*
         * Deal with array values in clauses with IN or BETWEEN operators
         */

        if (empty($value)) {
            throw Exceptional::{'df/opal/query/UnexpectedValue'}(
                'Array based clause values must have at least one entry'
            );
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $values = [];

        foreach ($value as $val) {
            $values[] = $this->normalizeScalarClauseValue($val, $allowAlias);
        }

        return $values;
    }

    public function normalizeScalarClauseValue($value, $allowAlias = false)
    {
        /*
         * Convert a clause value into a string to be used in a query, mainly for clauses.
         * If it is an intrinsic value (ie not a field) it should be bound to the statement
         * and the binding key returned instead.
         */

        if ($value instanceof opal\query\IField) {
            $valString = $this->defineFieldReference($value, $allowAlias);
        } elseif (is_array($value)) {
            throw Exceptional::{'df/opal/query/UnexpectedValue'}(
                'Expected a scalar as query value, found an array'
            );
        } else {
            $valString = ':' . $this->_stmt->autoBind($value);
        }

        return $valString;
    }



    // Expression
    public function defineExpression(opal\query\IExpression $expression)
    {
        $elements = $expression->getElements();
        $output = [];

        foreach ($elements as $element) {
            if ($element instanceof opal\query\IField) {
                $output[] = $this->defineFieldReference(
                    $element,
                    false,
                    $this->_query instanceof opal\query\IUpdateQuery ||
                    $this->_query instanceof opal\query\IDeleteQuery
                );
            } elseif ($element instanceof opal\query\IExpressionValue) {
                $output[] = ':' . $this->_stmt->autoBind($element->getValue());
            } elseif ($element instanceof opal\query\IExpressionOperator) {
                $output[] = $element->getOperator();
            } elseif ($element instanceof opal\query\ICorrelationQuery) {
                Glitch::incomplete($element);
            } elseif ($element instanceof opal\query\IExpression) {
                $output[] = '(' . $this->defineExpression($element) . ')';
            }
        }

        return implode(' ', $output);
    }


    // Groups
    public function writeGroupSection()
    {
        if (!$this->_query instanceof opal\query\IGroupableQuery) {
            return $this;
        }

        $groups = $this->_query->getGroupFields();

        if (empty($groups)) {
            return $this;
        }

        $groupFields = [];

        foreach ($groups as $field) {
            foreach ($field->dereference() as $field) {
                $directiveString = $this->defineFieldReference($field, true);
                $groupFields[] = $directiveString;
            }
        }

        $this->_stmt->appendSql("\n" . 'GROUP BY ' . implode(', ', $groupFields));
    }


    // Order
    public function writeOrderSection($forUpdateOrDelete = false, $checkSourceAlias = false)
    {
        if (!$this->_query instanceof opal\query\IOrderableQuery) {
            return $this;
        }

        $directives = $this->_query->getOrderDirectives();

        if (empty($directives)) {
            return $this;
        }

        if ($checkSourceAlias) {
            $sourceAlias = $this->_query->getSource()->getAlias();
        } else {
            $sourceAlias = null;
        }

        $orderFields = [];

        foreach ($directives as $directive) {
            $field = $directive->getField();

            if ($checkSourceAlias && $field->getSourceAlias() != $sourceAlias) {
                break;
            }

            foreach ($field->dereference() as $field) {
                if ($forUpdateOrDelete) {
                    $directiveString = $this->_adapter->quoteIdentifier($field->getName());
                } else {
                    $directiveString = $this->defineFieldReference($field, true, $forUpdateOrDelete);
                }

                $isDescending = $directive->isDescending();

                switch ($directive->getNullOrder()) {
                    case 'first':
                        $orderFields[] = 'ISNULL(' . $directiveString . ') DESC';
                        break;

                    case 'last':
                        $orderFields[] = 'ISNULL(' . $directiveString . ') ASC';
                        break;

                    case 'ascending':
                        break;

                    case 'descending':
                        $orderFields[] = 'ISNULL(' . $directiveString . ') ' . ($isDescending ? 'DESC' : 'ASC');
                        break;
                }

                if ($isDescending) {
                    $directiveString .= ' DESC';
                } else {
                    $directiveString .= ' ASC';
                }

                $orderFields[] = $directiveString;
            }
        }

        if (!empty($orderFields)) {
            $this->_stmt->appendSql("\n" . 'ORDER BY ' . implode(', ', $orderFields));
        }

        return $this;
    }


    // Limit
    public function writeLimitSection($forUpdateOrDelete = false)
    {
        if ($forUpdateOrDelete) {
            // Some servers cannot deal with offsets in updates / deletes
            if ($this->_query instanceof opal\query\ILimitableQuery
            && null !== ($limit = $this->_query->getLimit())) {
                $this->_stmt->appendSql("\n" . $this->defineLimit($limit));
            }

            return null;
        }


        $limit = null;
        $offset = null;

        if ($this->_query instanceof opal\query\ILimitableQuery) {
            $limit = $this->_query->getLimit();
        }

        if ($this->_query instanceof opal\query\IOffsettableQuery) {
            $offset = $this->_query->getOffset();
        }

        if ($limit !== null || $offset !== null) {
            $this->_stmt->appendSql("\n" . $this->defineLimit($limit, $offset));
        }

        return $this;
    }
}
