<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms;

use df;
use df\core;
use df\opal;
    
abstract class QueryExecutor implements IQueryExecutor {

    protected $_stmt;
    protected $_query;
    protected $_adapter;

    public static function factory(IAdapter $adapter, opal\query\IQuery $query=null, IStatement $stmt=null) {
        $type = $adapter->getServerType();
        $class = 'df\\opal\\rdbms\\variant\\'.$type.'\\QueryExecutor';
        
        if(!class_exists($class)) {
            throw new RuntimeException(
                'There is no query executor available for '.$type
            );
        }
        
        return new $class($adapter, $query);
    }

    protected function __construct(IAdapter $adapter, opal\query\IQuery $query=null, IStatement $stmt=null) {
        $this->_adapter = $adapter;
        $this->_query = $query;

        if(!$stmt) {
            $stmt = $adapter->prepare('');
        }

        $this->_stmt = $stmt;
    }

    public function getAdapter() {
        return $this->_adapter;
    }

    public function getQuery() {
        return $this->_query;
    }

    public function getStatement() {
        return $this->_stmt;
    }


// Count
    public function countTable($tableName) {
        $sql = 'SELECT COUNT(*) as total FROM '.$this->_adapter->quoteIdentifier($tableName);
        $res = $this->_adapter->prepare($sql)->executeRead();
        $row = $res->extract();
        
        return (int)$row['total'];
    }


// Read
    public function executeReadQuery($tableName, opal\query\IField $keyField=null, opal\query\IField $valField=null, $forFetch=false, $forCount=false) {
        if($this->_query->getSourceManager()->countSourceAdapters() == 1) {
            return $this->executeLocalReadQuery($tableName, $keyField, $valField, $forFetch, $forCount);
        } else {
            return $this->executeRemoteJoinedReadQuery($tableName, $keyField, $valField, $forCount);
        }
    }
     
    public function executeLocalReadQuery($tableName, opal\query\IField $keyField=null, opal\query\IField $valField=null, $forFetch=false, $forCount=false) {
        $source = $this->_query->getSource();
        $outFields = array();
        $requiresBatchIterator = false;
        $supportsProcessors = $source->getAdapter()->supportsQueryFeature(opal\query\IQueryFeatures::VALUE_PROCESSOR);
        
        if(!$forCount) {
            $requiresBatchIterator = $forFetch
                || !empty($keyField) 
                || !empty($valField) 
                || $supportsProcessors;
        }
        

        // Populates
        $populates = array();
        $populateFields = array();

        if(!$forCount && $this->_query instanceof opal\query\IPopulatableQuery) {
            $populates = $this->_query->getPopulates();

            if(!empty($populates)) {
                $requiresBatchIterator = true;
            }
        }
        
        
        // Attachments
        $attachments = array();
        $attachFields = array();
        
        if(!$forCount && $this->_query instanceof opal\query\IAttachableQuery) {
            $attachments = $this->_query->getAttachments();
            
            if(!empty($attachments)) {
                $requiresBatchIterator = true;
                
                // Get fields that need to be fetched from source for attachment clauses
                foreach($attachments as $attachment) {
                    foreach($attachment->getNonLocalFieldReferences() as $field) {
                        foreach($field->dereference() as $derefField) {
                            $qName = $derefField->getQualifiedName();
                            $attachFields[] = $this->defineField($derefField, $qName);
                        }
                    }
                }
            }
        }
        
        
        
        // Joins
        $joinSql = null;
        $joinSources = array();
        $joinFields = array();
        
        if($this->_query instanceof opal\query\IJoinProviderQuery) {
            $joins = $this->_query->getJoins();
            
            if(!empty($joins)) {
                // Build join statements
                foreach($joins as $join) {
                    $joinSources[] = $joinSource = $join->getSource();

                    $jExec = QueryExecutor::factory($this->_adapter, $join);
                    $joinSql .= "\n".$jExec->buildJoin($this->_stmt);
                    
                    if($supportsProcessors) {
                        $requiresBatchIterator = true;
                    }
                }
                 
                 
                // Get join source fields
                foreach($joinSources as $joinSource) {  
                    foreach($joinSource->getDereferencedOutputFields() as $field) {
                        if($requiresBatchIterator) {
                            $fieldAlias = $field->getQualifiedName();
                        } else {
                            $fieldAlias = $field->getAlias();
                        }
            
                        $joinFields[] = $this->defineField($field, $fieldAlias);
                    }
                }
            }
        }

        
        
        // Fields
        foreach($source->getDereferencedOutputFields() as $field) {
            if($requiresBatchIterator) {
                $fieldAlias = $field->getQualifiedName();
            } else {
                $fieldAlias = $field->getAlias();
            }

            $field->setLogicalAlias($fieldAlias);
            $outFields[] = $this->defineField($field, $fieldAlias);
        }
        
        
        if(!$forCount) {
            /*
             * We need to create 3 separate arrays in reverse order and then merge them
             * as we have to definitively know if a BatchIterator is required when getting
             * local fields. The only way to know this is to do attachments and joins first.
             */
            $outFields = array_unique(array_merge($outFields, $joinFields, $attachFields, $populateFields));
        }
        
        $distinct = $this->_query instanceof opal\query\IDistinctQuery && $this->_query->isDistinct() ? ' DISTINCT' : null;
        
        $this->_stmt->appendSql(
            'SELECT'.$distinct."\n".'  '.implode(','."\n".'  ', $outFields)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($tableName).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias()).
            $joinSql
        );
        
        
        $this->writeWhereClauseSection();
        $this->writeGroupSection();
        $this->writeHavingClauseSection();
        
        if($forCount) {
            $this->_stmt->prependSql('SELECT COUNT(*) as count FROM (')->appendSql(') as baseQuery');
        } else {
            $this->writeOrderSection();
            $this->writeLimitSection();
        }
        
        $res = $this->_stmt->executeRead();
        
        if(!$forCount && $requiresBatchIterator) {
            // We have keyField, valField, attachments and / or value processors or is for fetch
            
            $output = new opal\query\result\BatchIterator($source, $res);
            $output->addSources($joinSources)
                ->isForFetch((bool)$forFetch)
                ->setPopulates($populates)
                ->setAttachments($attachments)
                ->setListKeyField($keyField)
                ->setListValueField($valField);
                
            return $output;
        }
        
        return $res;
    }
            
            
    public function executeRemoteJoinedReadQuery($tableName, opal\query\IField $keyField=null, opal\query\IField $valField=null, $forCount=false) {
        $source = $this->_query->getSource();
        $primaryAdapterHash = $source->getAdapterHash();
        
        $outFields = array();
        $aggregateFields = array();
        
        foreach($source->getAllDereferencedFields() as $field) {
            $qName = $field->getQualifiedName();
            
            if($field instanceof opal\query\IAggregateField) {
                $aggregateFields[$qName] = $field;
            } else {
                $field->setLogicalAlias($qName);
                $outFields[$qName] = $this->defineField($field, $qName);
            }
        }
        
        
        // Joins & fields
        $remoteJoins = array();
        $localJoins = array();
        $joinSql = null;
        
        foreach($this->_query->getJoins() as $joinSourceAlias => $join) {
            $joinSource = $join->getSource();
            $hash = $joinSource->getAdapterHash();
            
            // Test to see if this is a remote join
            if($hash != $primaryAdapterHash || $join->referencesSourceAliases(array_keys($remoteJoins))) {
                $remoteJoins[$joinSourceAlias] = $join;
                continue;
            }
            
            $localJoins[$joinSourceAlias] = $join;
            
            foreach($joinSource->getAllDereferencedFields() as $field) {
                $qName = $field->getQualifiedName();
                
                if($field instanceof opal\query\IAggregateField) {
                    $aggregateFields[$qName] = $field;
                } else {
                    $outFields[$qName] = $this->defineField($field, $qName);
                }
            }
            
            $jExec = QueryExecutor::factory($this->_adapter, $join);
            $joinSql .= "\n".$jExec->buildJoin($this->_stmt);
        }
        

        $this->_stmt->appendSql(
            'SELECT'."\n".'  '.implode(','."\n".'  ', $outFields)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($tableName).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias()).
            $joinSql
        );
        
        
        
        // Filter results
        if($this->_query instanceof opal\query\IWhereClauseQuery && $this->_query->hasWhereClauses()) {
            $clauseList = $this->_query->getWhereClauseList();
            
            /*
             * We can filter down the results even though the join is processed locally -
             * we just need to only add where clauses that are local to the primary source
             * The main algorithm for finding those clauses is in the clause list class
             */  
            if(null !== ($joinClauseList = $clauseList->getClausesFor($source))) {
                $this->writeWhereClauseList($joinClauseList);
            }
        }
        
        
        /*
         * The following is a means of tentatively ordering and limiting the result set
         * used to create the join. It probably needs a bit of TLC :)
         */
        if(!$forCount) {
            $isPrimaryOrder = true;
            
            if($this->_query instanceof opal\query\IOrderableQuery) {
                $isPrimaryOrder = $this->_query->isPrimaryOrderSource();
            }
            
            if(empty($aggregateFields) && $isPrimaryOrder) {
                $this->writeOrderSection(false, true);
                $this->writeLimitSection();
            }
        }
        
        $arrayManipulator = new opal\query\result\ArrayManipulator($source, $this->_stmt->executeRead()->toArray(), true);
        return $arrayManipulator->applyRemoteJoinQuery($this->_query, $localJoins, $remoteJoins, $keyField, $valField, $forCount);
    }



// Insert
    public function executeInsertQuery($tableName) {
        $this->_stmt->appendSql('INSERT INTO '.$this->_adapter->quoteIdentifier($tableName));
        
        $fields = array();
        $values = array();
        
        foreach($this->_query->getRow() as $field => $value) {
            $fields[] = $this->_adapter->quoteIdentifier($field);
            $values[] = ':'.$field;

            if(is_array($value)) {
                if(count($value) == 1) {
                    // This is a total hack - you need to trace the real problem with multi-value keys
                    $value = array_shift($value);
                } else {
                    core\dump($value, $field, 'You need to trace back multi key primary field retention');
                }
            }

            $this->_stmt->bind($field, $value);
        }
        
        $this->_stmt->appendSql(' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')');

        if($this->_query->ifNotExists()) {
            $this->_stmt->appendSql(' ON DUPLICATE KEY UPDATE '.$fields[0].'='.$fields[0]);
        }

        $this->_stmt->executeWrite();
        
        return $this->_adapter->getLastInsertId();
    }

// Batch insert
    public function executeBatchInsertQuery($tableName) {
        $this->_stmt->appendSql('INSERT INTO '.$this->_adapter->quoteIdentifier($tableName));
        
        $rows = array();
        $fields = array();
        $fieldList = $this->_query->getFields();
        
        foreach($fieldList as $field) {
            $fields[] = $this->_adapter->quoteIdentifier($field);
        }
        
        foreach($this->_query->getRows() as $row) {
            foreach($fieldList as $key) {
                $id = $this->_stmt->generateUniqueKey();
                $value = null;
                
                if(isset($row[$key])) {
                    $value = $row[$key];
                }
                
                $this->_stmt->bind($id, $value);
                $row[$key] = ':'.$id;
            }
            
            $rows[] = '('.implode(',', $row).')';
        }
        
        
        $this->_stmt->appendSql(' ('.implode(',', $fields).') VALUES '.implode(', ', $rows));
        $this->_stmt->executeWrite();
        
        return count($rows);
    }

// Replace
    public function executeReplaceQuery($tableName) {
        $this->_stmt->appendSql('INSERT INTO '.$this->_adapter->quoteIdentifier($tableName));
        
        $fields = array();
        $values = array();
        $duplicates = array();
        
        foreach($this->_query->getRow() as $field => $value) {
            $fields[] = $fieldString = $this->_adapter->quoteIdentifier($field);
            $values[] = ':'.$field;
            $duplicates[] = $fieldString.'=VALUES('.$fieldString.')';
            $this->_stmt->bind($field, $value);
        }
        
        $this->_stmt->appendSql(' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')');
        $this->_stmt->appendSql("\n".'ON DUPLICATE KEY UPDATE '.implode(', ', $duplicates));
        $this->_stmt->executeWrite();
        
        return $this->_adapter->getLastInsertId();
    }

// Batch replace
    public function executeBatchReplaceQuery($tableName) {
        $this->_stmt->appendSql('INSERT INTO '.$this->_adapter->quoteIdentifier($tableName));
        
        $rows = array();
        $fields = array();
        $fieldList = $this->_query->getFields();
        $duplicates = array();
        
        foreach($fieldList as $field) {
            $fields[] = $fieldString = $this->_adapter->quoteIdentifier($field);
            $duplicates[] = $fieldString.'=VALUES('.$fieldString.')';
        }
        
        foreach($this->_query->getRows() as $row) {
            foreach($fieldList as $key) {
                $id = $this->_stmt->generateUniqueKey();
                $value = null;
                
                if(isset($row[$key])) {
                    $value = $row[$key];
                }
                
                $this->_stmt->bind($id, $value);
                $row[$key] = ':'.$id;
            }
            
            $rows[] = '('.implode(',', $row).')';
        }
        
        $this->_stmt->appendSql(' ('.implode(',', $fields).') VALUES '.implode(', ', $rows));
        $this->_stmt->appendSql("\n".'ON DUPLICATE KEY UPDATE '.implode(', ', $duplicates));
        $this->_stmt->executeWrite();
        
        return count($rows);
    }

// Update
    public function executeUpdateQuery($tableName) {
        if(!$this->_query instanceof opal\query\IUpdateQuery) {
            throw new opal\rdbms\UnexpectedValueException(
                'Executor query is not an update'
            );
        }

        $this->_stmt->appendSql('UPDATE '.$this->_adapter->quoteIdentifier($tableName).' SET');
        $values = array();
        
        foreach($this->_query->getValueMap() as $field => $value) {
            // TODO: check for expression

            $id = $this->_stmt->generateUniqueKey();
            $values[] = $this->_adapter->quoteIdentifier($field).' = :'.$id;
            $this->_stmt->bind($id, $value);
        }
        
        $this->_stmt->appendSql("\n  ".implode(','."\n".'  ', $values));
        $this->writeWhereClauseSection(null, true);
        
        if(!$this->_adapter->supports(opal\rdbms\adapter\Base::UPDATE_LIMIT)) {
            $this->writeOrderSection(true);
            $this->writeLimitSection(true);
        }

        return $this->_stmt->executeWrite();
    }

// Delete
    public function executeDeleteQuery($tableName) {
        if(!$this->_query instanceof opal\query\IDeleteQuery) {
            throw new opal\rdbms\UnexpectedValueException(
                'Executor query is not a delete'
            );
        }

        $this->_stmt->appendSql('DELETE FROM '.$this->_adapter->quoteIdentifier($tableName));
        $this->writeWhereClauseSection(null, true);
        
        if($this->_adapter->supports(opal\rdbms\adapter\Base::DELETE_LIMIT)) {
            $this->writeOrderSection(true);
            $this->writeLimitSection(true);
        }

        return $this->_stmt->executeWrite();
    }


// Remote data
    public function fetchRemoteJoinData($tableName, array $rows) {
        if(!$this->_query instanceof opal\query\IJoinQuery) {
            throw new opal\rdbms\UnexpectedValueException(
                'Executor query is not a join'
            );
        }

        $sources = array();
        $outFields = array();

        $source = $this->_query->getSource();
        $sources[$source->getUniqueId()] = $source;
        $parentSource = $this->_query->getParentSource();
        $sources[$parentSource->getUniqueId()] = $parentSource;

        foreach($this->_query->getSource()->getAllDereferencedFields() as $field) {
            if($field instanceof opal\query\IAggregateField) {
                continue;
            }
            
            $fieldAlias = $field->getQualifiedName();
            $field->setLogicalAlias($fieldAlias);
            $outFields[] = $this->defineField($field, $fieldAlias);
        }
        
        $this->_stmt->appendSql(
            'SELECT'."\n".'  '.implode(','."\n".'  ', $outFields)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($tableName).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias())
        );
        
        $clauses = $this->_query->getJoinClauseList();
        
        if(!$clauses->isEmpty() && $clauses->isLocalTo($sources)) {
            $this->writeWhereClauseList($clauses, $rows);
        }
        
        return $this->_stmt->executeRead()->toArray();
    }

    public function fetchAttachmentData($tableName, array $rows) {
        if(!$this->_query instanceof opal\query\IAttachQuery) {
            throw new opal\rdbms\UnexpectedValueException(
                'Executor query is not an attachment'
            );
        }

        $outFields = array();
        $sources = array();

        $source = $this->_query->getSource();
        $sources[$source->getUniqueId()] = $source;

        foreach($source->getAllDereferencedFields() as $field) {
            if($field instanceof opal\query\IAggregateField) {
                continue;
            }
            
            $fieldAlias = $field->getQualifiedName();
            $field->setLogicalAlias($fieldAlias);
            $outFields[] = $this->defineField($field, $fieldAlias);
        }




        $joinSql = null;
        $joinsApplied = false;
        
        if($this->_query->getSourceManager()->countSourceAdapters() == 1) {
            foreach($this->_query->getJoins() as $joinSourceAlias => $join) {
                $joinSource = $join->getSource();
                $sources[$joinSource->getUniqueId()] = $joinSource;

                $jExec = QueryExecutor::factory($this->_adapter, $join);
                $joinSql .= "\n".$jExec->buildJoin($this->_stmt);

                foreach($joinSource->getAllDereferencedFields() as $field) {
                    if(!$field instanceof opal\query\IAggregateField) {
                        $outFields[] = $this->defineField($field, $field->getQualifiedName());
                    }
                }
            }

            $joinsApplied = true;
        }


        $this->_stmt->appendSql(
            'SELECT'."\n".'  '.implode(','."\n".'  ', array_unique($outFields))."\n".
            'FROM '.$this->_adapter->quoteIdentifier($tableName).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias()).
            $joinSql
        );


        $joinClauses = $this->_query->getJoinClauseList();
        $whereClauses = $this->_query->getWhereClauseList();

        $canUseJoinClauses = !$joinClauses->isEmpty() && $joinClauses->isLocalTo($sources);
        $canUseWhereClauses = $clausesApplied = !$whereClauses->isEmpty() && $whereClauses->isLocalTo($sources);

        if($canUseJoinClauses) {
            if($canUseWhereClauses) {
                $clauses = new opal\query\clause\ListBase($this->_query->getParentQuery());
                $clauses->_addClause($joinClauses);
                $clauses->_addClause($whereClauses);
            } else {
                $clauses = $joinClauses;
            }
        } else if($canUseWhereClauses) {
            $clauses = $whereClauses;
        } else {
            $clauses = null;
        }


        // TODO: add filter clause for attachment clauses

        if($clauses) {
            $this->writeWhereClauseList($clauses, $rows);
        }

        $manipulator = new opal\query\result\ArrayManipulator($source, $this->_stmt->executeRead()->toArray(), true);
        return $manipulator->applyAttachmentDataQuery($this->_query, $joinsApplied, $clausesApplied);
    }



// Correlations
    public function buildCorrelation(IStatement $stmt) {
        if(!$this->_query instanceof opal\query\ICorrelationQuery) {
            throw new opal\rdbms\UnexpectedValueException(
                'Executor query is not a correlation'
            );
        }

        $this->_stmt->setKeyIndex($stmt->getKeyIndex());

        $source = $this->_query->getSource();
        $outFields = array();

        $supportsProcessors = $source->getAdapter()->supportsQueryFeature(
            opal\query\IQueryFeatures::VALUE_PROCESSOR
        );
        
        
        // Fields
        $fieldAlias = $this->_query->getFieldAlias();
        $field = $source->getFieldByAlias($fieldAlias);
        $outFields[/*$fieldAlias*/] = $this->defineField($field, $fieldAlias);


        // Joins
        $joinSql = null;
        
        foreach($this->_query->getJoins() as $joinSourceAlias => $join) {
            $joinSource = $join->getSource();
            $hash = $joinSource->getAdapterHash();

            // TODO: make sure it's not a remote join

            $exec = self::factory($this->_adapter, $join);
            $joinSql .= "\n".$exec->buildJoin($this->_stmt);
        }
        
        
        // SQL
        $this->_stmt->appendSql(
            'SELECT'."\n".'  '.implode(','."\n".'  ', $outFields)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($source->getAdapter()->getDelegateQueryAdapter()->getName()).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias()).
            $joinSql
        );
        

        // Clauses
        $joinClauses = $this->_query->getJoinClauseList();
        $whereClauses = $this->_query->getWhereClauseList();

        if(!$joinClauses->isEmpty()) {
            if(!$whereClauses->isEmpty()) {
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
        $this->writeLimitSection();

        $stmt->importBindings($this->_stmt);
        $stmt->setKeyIndex($this->_stmt->getKeyIndex());
        
        return $this->_stmt->getSql();
    }



// Join
    public function buildJoin(IStatement $stmt) {
        if(!$this->_query instanceof opal\query\IJoinQuery) {
            throw new opal\rdbms\UnexpectedValueException(
                'Executor query is not a join'
            );
        }

        $this->_stmt->setKeyIndex($stmt->getKeyIndex());

        switch($this->_query->getType()) {
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
        
        $this->_stmt->appendSql(
            ' JOIN '.$this->_adapter->quoteIdentifier($this->_query->getSource()->getAdapter()->getDelegateQueryAdapter()->getName()).
            ' AS '.$this->_adapter->quoteTableAliasDefinition($this->_query->getSourceAlias())
        );
                   
        $onClauses = $this->_query->getJoinClauseList();
        $whereClauses = $this->_query->getWhereClauseList();
        $onClausesEmpty = $onClauses->isEmpty();
        $whereClausesEmpty = $whereClauses->isEmpty();
        $clauses = null;

        if(!$onClausesEmpty && !$whereClausesEmpty) {
            $clauses = new opal\query\clause\ListBase($this->_query);
            $clauses->_addClause($onClauses);
            $clauses->_addClause($whereClauses);
        } else if(!$onClausesEmpty) {
            $clauses = $onClauses;
        } else if(!$whereClausesEmpty) {
            $clauses = $whereClauses;
        }

        if($clauses) {
            $this->writeJoinClauseList($clauses);
        }

        $stmt->importBindings($this->_stmt);
        $stmt->setKeyIndex($this->_stmt->getKeyIndex());
        
        return $this->_stmt->getSql();
    }



// Fields
    public function defineField(opal\query\IField $field, $alias=null) {
        /*
         * This method is used in many places to get a string representation of a 
         * query field. If $defineAlias is true, it is suffixed with AS <alias> and
         * denotes a field in the initial SELECT statement.
         */
        $defineAlias = true;

        if($alias === false) {
            $defineAlias = false;
        }
        
        if($field instanceof opal\query\IWildcardField) {
            // Wildcard
            $output = $this->_adapter->quoteTableAliasReference($field->getSourceAlias()).'.*';
            $defineAlias = false;
            
        } else if($field instanceof opal\query\IAggregateField) {
            // Aggregate
            $targetField = $field->getTargetField();
            
            if($targetField instanceof opal\query\IWildcardField) {
                $targetFieldString = '*';
            } else {
                $targetFieldString = $this->defineField($field->getTargetField(), false);
            }
            
            $output = $field->getTypeName().'('.$targetFieldString.')';
            
        } else if($field instanceof opal\query\IIntrinsicField) {
            // Intrinsic
            $output = $this->_adapter->quoteTableAliasReference($field->getSourceAlias()).'.'.
                      $this->_adapter->quoteIdentifier($field->getName());
            
        } else if($field instanceof opal\query\IVirtualField) {
            throw new InvalidArgumentException(
                'Virtual fields can not be used directly'
            );
        } else if($field instanceof opal\query\ICorrelationField) {
            $exec = self::factory($this->_adapter, $field->getCorrelationQuery());
            $sql = $exec->buildCorrelation($this->_stmt);
            $output = '('."\n".'    '.str_replace("\n", "\n    ", $sql)."\n".'  )';
        } else {
            throw new opal\rdbms\UnexpectedValueException(
                'Field type '.get_class($field).' is not currently supported'
            );
        }
        
        if($defineAlias) {
            if($alias === null) {
                $alias = $field->getAlias();
            }

            $output .= ' AS '.$this->_adapter->quoteFieldAliasDefinition($alias);
        }
        
        return $output;
    }

    public function defineFieldReference(opal\query\IField $field, $allowAlias=false, $forUpdateOrDelete=false) {
        $isDiscreetAggregate = $allowAlias
            && $field instanceof opal\query\IAggregateField 
            && $field->hasDiscreetAlias();

        $deepNest = false;

        if(!$isDiscreetAggregate 
        && !$allowAlias 
        && $this->_query instanceof opal\query\IParentQueryAware
        && $this->_query->isSourceDeepNested($field->getSource())) {
            $allowAlias = true;
            $deepNest = true;
        }

        if($isDiscreetAggregate) {
            // Reference an aggregate by alias
            $fieldString = $this->_adapter->quoteFieldAliasReference($field->getAlias());
        } else if($forUpdateOrDelete) {
            /*
             * If used on an aggregate field or for update or delete, the name must
             * be used on some sql servers
             */
            return $this->_adapter->quoteIdentifier($field->getName());
        } else if($allowAlias && ($alias = $field->getLogicalAlias())) {
            // Defined in a field list
            if($deepNest) {
                return $this->_adapter->quoteFieldAliasDefinition($alias);
            } else {
                return $this->_adapter->quoteFieldAliasReference($alias);
            }
        } else if($field instanceof opal\query\ICorrelationField) {
            return $this->_adapter->quoteFieldAliasReference($field->getLogicalAlias());
        } else {
            return $this->_adapter->quoteTableAliasReference($field->getSourceAlias()).'.'.
                   $this->_adapter->quoteIdentifier($field->getName());
        }
    }


// Clauses
    public function writeJoinClauseSection() {
        if(!$this->_query instanceof opal\query\IJoinClauseQuery || !$this->_query->hasJoinClauses()) {
            return $this;
        }

        return $this->writeJoinClauseList($this->_query->getJoinClauseList());
    }

    public function writeJoinClauseList(opal\query\IClauseList $clauses) {
        if($clauses->isEmpty()) {
            return $this;
        }

        $clauseString = $this->defineClauseList($clauses);
        
        if(!empty($clauseString)) {
            $this->_stmt->appendSql("\n".'  ON '.$clauseString);
        }

        return $this;
    }

    public function writeWhereClauseSection(array $remoteJoinData=null, $forUpdateOrDelete=false) {
        if(!$this->_query instanceof opal\query\IWhereClauseQuery || !$this->_query->hasWhereClauses()) {
            return $this;
        }

        return $this->writeWhereClauseList($this->_query->getWhereClauseList(), $remoteJoinData, $forUpdateOrDelete);
    }

    public function writeWhereClauseList(opal\query\IClauseList $clauses, array $remoteJoinData=null, $forUpdateOrDelete=false) {
        if($clauses->isEmpty()) {
            return $this;
        }

        $clauseString = $this->defineClauseList($clauses, $remoteJoinData, false, $forUpdateOrDelete);
        
        if(!empty($clauseString)) {
            $this->_stmt->appendSql("\n".'WHERE '.$clauseString);
        }

        return $this;
    }
    
    public function writeHavingClauseSection() {
        if(!$this->_query instanceof opal\query\IHavingClauseQuery || !$this->_query->hasHavingClauses()) {
            return $this;
        }

        return $this->writeHavingClauseList($this->_query->getHavingClauseList());
    }

    public function writeHavingClauseList(opal\query\IClauseList $clauses) {
        if($clauses->isEmpty()) {
            return $this;
        }

        $clauseString = $this->defineClauseList($clauses, null, true);
        
        if(!empty($clauseString)) {
            $this->_stmt->appendSql("\n".'HAVING '.$clauseString);
        }

        return $this;
    }

    public function defineClauseList(opal\query\IClauseList $list, array $remoteJoinData=null, $allowAlias=false, $forUpdateOrDelete=false) {
        $output = '';
        
        foreach($list->toArray() as $clause) {
            if($clause instanceof opal\query\IClause) {
                $clauseString = $this->defineClause(
                    $clause, $remoteJoinData, $allowAlias, $forUpdateOrDelete
                );
            } else if($clause instanceof opal\query\IClauseList) {
                $clauseString = $this->defineClauseList(
                    $clause, $remoteJoinData, $allowAlias, $forUpdateOrDelete
                );
            }
            
            if(empty($clauseString)) {
                continue;
            }
            
            if(!empty($output)) {
                if($clause->isOr()) {
                    $separator = ' OR ';
                } else {
                    $separator = ' AND ';
                }
                
                $clauseString = $separator.$clauseString;
            }
            
            $output .= $clauseString;
        }
        
        if(empty($output)) {
            return null;
        }
        
        return '('.$output.')';
    }

    public function defineClause(opal\query\IClause $clause, array $remoteJoinData=null, $allowAlias=false, $forUpdateOrDelete=false) {
        $field = $clause->getField();
        $operator = $clause->getOperator();
        $value = $clause->getPreparedValue();
        $fieldString = $this->defineFieldReference($field, false, $forUpdateOrDelete);

        if($remoteJoinData !== null) {
            /*
             * If we're defining a clause for a remote join we will be comparing
             * a full dataset, but with an operator defined for a single value
             */

            if($value instanceof opal\query\ICorrelationQuery) {
                $value = clone $value;

                $correlationSource = $value->getCorrelationSource();
                $correlationSourceAlias = $correlationSource->getAlias();

                foreach($value->getCorrelatedClauses($correlationSource) as $correlationClause) {
                    $field = $correlationClause->getField();

                    if($field->getSourceAlias() == $correlationSourceAlias) {
                        core\stub($field, 'What exactly are we supposed to do with left hand side clause correlations???!?!?!?!?');
                    }

                    $correlationValue = $correlationClause->getValue();

                    if($correlationValue instanceof opal\query\IField
                    && $correlationValue->getSourceAlias() == $correlationSourceAlias) {
                        $qName = $correlationValue->getQualifiedName();
                        $valueData = $this->_getClauseValueForRemoteJoinData($correlationClause, $remoteJoinData, $qName, $operator);

                        $correlationClause->setOperator($operator);
                        $correlationClause->setValue($valueData);
                    }
                }
            } else if($value instanceof opal\query\IField) {
                $qName = $value->getQualifiedName();
                $value = $this->_getClauseValueForRemoteJoinData($clause, $remoteJoinData, $qName, $operator);
            }
        }
        
        if($value instanceof opal\query\ICorrelationQuery) {
            // Subqueries need to be handled separately
            return $this->defineClauseCorrelation($field, $fieldString, $operator, $value, $allowAlias);
        } else {
            // Define a standard expression
            return $this->defineClauseExpression($field, $fieldString, $operator, $value, $allowAlias);
        }
    }

    protected function _getClauseValueForRemoteJoinData(opal\query\IClause $clause, array $remoteJoinData, $qName, &$operator) {
        $listData = array();
                
        foreach($remoteJoinData as $row) {
            if(isset($row[$qName])) {
                $listData[] = $row[$qName];
            } else {
                $listData[] = null;
            }
        }
        
        switch($operator) {
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_IN:
            case opal\query\clause\Clause::OP_LIKE:
            case opal\query\clause\Clause::OP_CONTAINS:
            case opal\query\clause\Clause::OP_BEGINS:
            case opal\query\clause\Clause::OP_ENDS:
                // Test using IN operator on data set
                $operator = opal\query\clause\Clause::OP_IN;
                return array_unique($listData);
                
            case opal\query\clause\Clause::OP_NEQ:
            case opal\query\clause\Clause::OP_NOT_IN:
            case opal\query\clause\Clause::OP_NOT_LIKE:
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
            case opal\query\clause\Clause::OP_NOT_BEGINS:
            case opal\query\clause\Clause::OP_NOT_ENDS:
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
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' cannot be used for a remote join'
                );
        }
    }

    public function defineClauseCorrelation(opal\query\IField $field, $fieldString, $operator, opal\query\ICorrelationQuery $correlation, $allowAlias=false) {
        $source = $correlation->getSource();
        $isSourceLocal = $source->getAdapterHash() == $this->_adapter->getDsnHash();
        $hasRemoteSources = $correlation->getSourceManager()->countSourceAdapters() > 1;
        $isRegexp = false;
        
        switch($operator) {
            case opal\query\clause\Clause::OP_CONTAINS:
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
            case opal\query\clause\Clause::OP_BEGINS:
            case opal\query\clause\Clause::OP_NOT_BEGINS:
            case opal\query\clause\Clause::OP_ENDS:
            case opal\query\clause\Clause::OP_NOT_ENDS:
                // Regexp clauses cannot be done inline
                $isRegexp = true;
        }
        
        if(!$isRegexp && $isSourceLocal && !$hasRemoteSources) {
            // The subquery can be put directly into the parent query
            return $this->defineClauseLocalCorrelation($field, $fieldString, $operator, $correlation);
        } else {
            // We have to execute the subquery and pass the values in manually
            return $this->defineClauseRemoteCorrelation($field, $fieldString, $operator, $correlation, $allowAlias);
        }
    }

    public function defineClauseLocalCorrelation(opal\query\IField $field, $fieldString, $operator, opal\query\ICorrelationQuery $correlation) {
        $limit = $correlation->getLimit();
        
        switch($operator) {
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_NEQ:
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
        return $fieldString.' '.strtoupper($operator).' ('."\n  ".str_replace("\n", "\n  ", $sql)."\n".')';
    }

    public function defineClauseRemoteCorrelation(opal\query\IField $field, $fieldString, $operator, opal\query\ICorrelationQuery $correlation, $allowAlias=false) {
        /*
         * Execute the subquery and get the result as a list.
         * Then depending on the operator, build the relevant clause
         */    
        $source = $correlation->getSource();
        
        if(null === ($targetField = $source->getFirstOutputDataField())) {
            throw new opal\query\ValueException(
                'Clause subquery does not have a distinct return field'
            );
        }
        
        $values = $correlation->toList($targetField->getName());
        
        switch($operator) {
            // = | IN()
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_IN:
                if(empty($values)) {
                    return $fieldString.' IS NULL';
                }
                
                return $fieldString.' IN ('.implode(',', $this->normalizeArrayClauseValue($values, $allowAlias)).')';
                
            // != | NOT IN()
            case opal\query\clause\Clause::OP_NEQ:
            case opal\query\clause\Clause::OP_NOT_IN:
                if(empty($values)) {
                    return $fieldString.' IS NOT NULL';
                }
                
                return $fieldString.' NOT IN '.$operator.' ('.implode(',', $this->normalizeArrayClauseValue($values, $allowAlias)).')';
                
            // > | >=
            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
                return $fieldString.' '.$operator.' '.$this->normalizeScalarClauseValue(max($values), $allowAlias);
                
            // < | <=
            case opal\query\clause\Clause::OP_LT: 
            case opal\query\clause\Clause::OP_LTE:
                return $fieldString.' '.$operator.' '.$this->normalizeScalarClauseValue(min($values), $allowAlias);
                
            // BETWEEN | NOT BETWEEN
            case opal\query\clause\Clause::OP_BETWEEN:
            case opal\query\clause\Clause::OP_NOT_BETWEEN:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not valid for clause subqueries'
                );
                
            
            // LIKE
            case opal\query\clause\Clause::OP_LIKE:
                return $fieldString.' REGEXP '.$this->normalizeScalarClauseValue(
                    core\string\Util::generateLikeMatchRegex($values), $allowAlias
                );
                
            // NOT LIKE
            case opal\query\clause\Clause::OP_NOT_LIKE:
                return $fieldString.' NOT REGEXP '.$this->normalizeScalarClauseValue(
                    core\string\Util::generateLikeMatchRegex($values), $allowAlias
                );
                
            // LIKE LIST OF %<value>%
            case opal\query\clause\Clause::OP_CONTAINS:
                foreach($values as &$value) {
                    $value = '%'.str_replace(array('_', '%'), array('\_', '\%'), $value).'%';
                }
                
                return $fieldString.' REGEXP '.$this->normalizeScalarClauseValue(
                    core\string\Util::generateLikeMatchRegex($values), $allowAlias
                );
                
            // NOT LIKE LIST OF %<value>%
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
                foreach($values as &$value) {
                    $value = '%'.str_replace(array('_', '%'), array('\_', '\%'), $value).'%';
                }
                
                return $fieldString.' NOT REGEXP '.$this->normalizeScalarClauseValue(
                    core\string\Util::generateLikeMatchRegex($values), $allowAlias
                );
                
            // LIKE LIST OF <value>%
            case opal\query\clause\Clause::OP_BEGINS:
                foreach($values as &$value) {
                    $value = str_replace(array('_', '%'), array('\_', '\%'), $value).'%';
                }
                
                return $fieldString.' REGEXP '.$this->normalizeScalarClauseValue(
                    core\string\Util::generateLikeMatchRegex($values), $allowAlias
                );
                
            // NOT LIKE LIST OF <value>%
            case opal\query\clause\Clause::OP_NOT_BEGINS:
                foreach($values as &$value) {
                    $value = str_replace(array('_', '%'), array('\_', '\%'), $value).'%';
                }
                
                return $fieldString.' NOT REGEXP '.$this->normalizeScalarClauseValue(
                    core\string\Util::generateLikeMatchRegex($values), $allowAlias
                );
                
            // LIKE LIST OF %<value>
            case opal\query\clause\Clause::OP_ENDS:
                foreach($values as &$value) {
                    $value = '%'.str_replace(array('_', '%'), array('\_', '\%'), $value);
                }
                
                return $fieldString.' REGEXP '.$this->normalizeScalarClauseValue(
                    core\string\Util::generateLikeMatchRegex($values), $allowAlias
                );
                
            // NOT LIKE LIST OF %<value>
            case opal\query\clause\Clause::OP_NOT_ENDS:
                foreach($values as &$value) {
                    $value = '%'.str_replace(array('_', '%'), array('\_', '\%'), $value);
                }
                
                return $fieldString.' REGEXP '.$this->normalizeScalarClauseValue(
                    core\string\Util::generateLikeMatchRegex($values), $allowAlias
                );
            
            
            default:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not recognized'
                );
        }
    }

    public function defineClauseExpression(opal\query\IField $field, $fieldString, $operator, $value, $allowAlias=false) {
        switch($operator) {
            // = | !=
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_NEQ:
                if($value === null) {
                    if($operator == '=') {
                        return $fieldString.' IS NULL';
                    } else {
                        return $fieldString.' IS NOT NULL';
                    }
                }
                
                return $fieldString.' '.$operator.' '.$this->normalizeScalarClauseValue($value, $allowAlias);
                
            // > | >= | < | <=
            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
            case opal\query\clause\Clause::OP_LT: 
            case opal\query\clause\Clause::OP_LTE:
                return $fieldString.' '.$operator.' '.$this->normalizeScalarClauseValue($value, $allowAlias);
                
            // <NOT> IN()
            case opal\query\clause\Clause::OP_IN:
            case opal\query\clause\Clause::OP_NOT_IN:
                $not = $operator == opal\query\clause\Clause::OP_NOT_IN;

                if(empty($value)) {
                    return '1 '.($not ? null : '!').'= 1';
                }

                $hasNull = false;

                if(in_array(null, $value)) {
                    $value = array_filter($value, function($a) { return $a !== null; });
                    $hasNull = true;
                }
                
                $output = $fieldString.($not ? ' NOT' : null).' IN ('.implode(',', $this->normalizeArrayClauseValue($value, $allowAlias)).')';

                if($hasNull) {
                    $output = '('.$output.' OR '.$fieldString.' IS'.($not ? ' NOT' : null).' NULL)';
                }

                return $output;
                
            // BETWEEN()
            case opal\query\clause\Clause::OP_BETWEEN:
                $value = $this->normalizeArrayClauseValue($value, $allowAlias);
                return $fieldString.' BETWEEN '.array_shift($value).' AND '.array_shift($value);
                
            // NOT BETWEEN()
            case opal\query\clause\Clause::OP_NOT_BETWEEN:
                $value = $this->normalizeArrayClauseValue($value, $allowAlias);
                return $fieldString.' NOT BETWEEN '.array_shift($value).' AND '.array_shift($value);
            
            // LIKE
            case opal\query\clause\Clause::OP_LIKE:
                return $fieldString.' LIKE '.$this->normalizeScalarClauseValue(
                    str_replace(array('?', '*'), array('_', '%'), $value), 
                    $allowAlias
                );
                
            // NOT LIKE
            case opal\query\clause\Clause::OP_NOT_LIKE:
                return $fieldString.' NOT LIKE '.$this->normalizeScalarClauseValue(
                    str_replace(array('?', '*'), array('_', '%'), $value), 
                    $allowAlias
                );
                
            // LIKE %<value>%
            case opal\query\clause\Clause::OP_CONTAINS:
                return $fieldString.' LIKE '.$this->normalizeScalarClauseValue(
                    '%'.str_replace(array('_', '%'), array('\_', '\%'), $value).'%', 
                    $allowAlias
                );
                
            // NOT LIKE %<value>%
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
                return $fieldString.' NOT LIKE '.$this->normalizeScalarClauseValue(
                    '%'.str_replace(array('_', '%'), array('\_', '\%'), $value).'%', 
                    $allowAlias
                );
            
            // LIKE <value>%
            case opal\query\clause\Clause::OP_BEGINS:
                return $fieldString.' LIKE '.$this->normalizeScalarClauseValue(
                    str_replace(array('_', '%'), array('\_', '\%'), $value).'%', 
                    $allowAlias
                );
                
            // NOT LIKE <value>%
            case opal\query\clause\Clause::OP_NOT_BEGINS:
                return $fieldString.' NOT LIKE '.$this->normalizeScalarClauseValue(
                    str_replace(array('_', '%'), array('\_', '\%'), $value).'%', 
                    $allowAlias
                );
                
            // LIKE %<value>
            case opal\query\clause\Clause::OP_ENDS:
                return $fieldString.' LIKE '.$this->normalizeScalarClauseValue(
                    '%'.str_replace(array('_', '%'), array('\_', '\%'), $value), 
                    $allowAlias
                );
                
            // NOT LIKE %<value>
            case opal\query\clause\Clause::OP_NOT_ENDS:
                return $fieldString.' NOT LIKE '.$this->normalizeScalarClauseValue(
                    '%'.str_replace(array('_', '%'), array('\_', '\%'), $value), 
                    $allowAlias
                );
            
            
            default:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not recognized'
                );
        }
    }

    public function normalizeArrayClauseValue($value, $allowAlias=false) {
        /*
         * Deal with array values in clauses with IN or BETWEEN operators
         */
            
        if(empty($value)) {
            throw new opal\query\ValueException(
                'Array based clause values must have at least one entry'
            );
        }
        
        if(!is_array($value)) {
            $value = array($value);
        }
        
        $values = array();
            
        foreach($value as $val) {
            $values[] = $this->normalizeScalarClauseValue($val, $allowAlias);
        }
        
        return $values;
    }

    public function normalizeScalarClauseValue($value, $allowAlias=false) {
        /*
         * Convert a clause value into a string to be used in a query, mainly for clauses.
         * If it is an intrinsic value (ie not a field) it should be bound to the statement
         * and the binding key returned instead.
         */
            
        if($value instanceof opal\query\IField) {
            $valString = $this->defineFieldReference($value, $allowAlias);
        } else if(is_array($value)) {
            throw new opal\query\ValueException(
                'Expected a scalar as query value, found an array'
            );
        } else {
            $valString = ':'.$this->_stmt->generateUniqueKey();
            $this->_stmt->bind($valString, $value);
        }
        
        return $valString;
    }



// Groups
    public function writeGroupSection() {
        if(!$this->_query instanceof opal\query\IGroupableQuery) {
            return $this;
        }

        $groups = $this->_query->getGroupFields();
            
        if(empty($groups)) {
            return $this;
        }

        $groupFields = array();
        
        foreach($groups as $field) {
            $groupFields[] = $this->defineFieldReference($field, true);
        }
        
        $this->_stmt->appendSql("\n".'GROUP BY '.implode(', ', $groupFields));
    }


// Order
    public function writeOrderSection($forUpdateOrDelete=false, $checkSourceAlias=false) {
        if(!$this->_query instanceof opal\query\IOrderableQuery) {
            return $this;
        }
            
        $directives = $this->_query->getOrderDirectives();
            
        if(empty($directives)) {
            return $this;
        }

        if($checkSourceAlias) {
            $sourceAlias = $this->_query->getSource()->getAlias();
        }

        $orderFields = array();
        
        foreach($directives as $directive) {
            $field = $directive->getField();
            
            if($checkSourceAlias && $field->getSourceAlias() != $sourceAlias) {
                break;
            }

            foreach($field->dereference() as $field) {
                if($forUpdateOrDelete) {
                    $directiveString = $this->_adapter->quoteIdentifier($field->getName());
                } else {
                    $directiveString = $this->defineFieldReference($field, true, $forUpdateOrDelete);
                }
                
                if($directive->isDescending()) {
                    $directiveString .= ' DESC';
                } else {
                    $directiveString .= ' ASC';
                }

                $orderFields[] = $directiveString;
            }
        }
        
        if(!empty($orderFields)) {
            $this->_stmt->appendSql("\n".'ORDER BY '.implode(', ', $orderFields));
        }

        return $this;
    }


// Limit
    public function writeLimitSection($forUpdateOrDelete=false) {
        if($forUpdateOrDelete) {
            // Some servers cannot deal with offsets in updates / deletes
            if($this->_query instanceof opal\query\ILimitableQuery
            && null !== ($limit = $this->_query->getLimit())) {
                $this->_stmt->appendSql("\n".$this->defineLimit($limit));
            }
            
            return null;
        }
        
        
        $limit = null;
        $offset = null;
        
        if($this->_query instanceof opal\query\ILimitableQuery) {
            $limit = $this->_query->getLimit();
        }
        
        if($this->_query instanceof opal\query\IOffsettableQuery) {
            $offset = $this->_query->getOffset();
        }
            
        if($limit !== null || $offset !== null) {
            $this->_stmt->appendSql("\n".$this->defineLimit($limit, $offset));
        }

        return $this;
    }
}