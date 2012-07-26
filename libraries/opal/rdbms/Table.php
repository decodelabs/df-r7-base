<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms;

use df;
use df\core;
use df\opal;

abstract class Table implements ITable, core\IDumpable {
    
    use opal\query\TQuery_ImplicitSourceEntryPoint;
    
    protected $_adapter;
    protected $_name;
    protected $_querySourceId;
    
    public static function factory(opal\rdbms\IAdapter $adapter, $name) {
        $type = $adapter->getServerType();
        $class = 'df\\opal\\rdbms\\variant\\'.$type.'\\Table';
        
        if(!class_exists($class)) {
            throw new RuntimeException(
                'There is no table handler available for '.$type
            );
        } else if(empty($name)) {
            throw new InvalidArgumentException(
                'Invalid table name'
            );
        }
        
        return new $class($adapter, $name);
    }
    
    protected function __construct(opal\rdbms\IAdapter $adapter, $name) {
        $this->_adapter = $adapter;
        $this->_setName($name);
    }
    
    public function getAdapter() {
        return $this->_adapter;
    }
    
    protected function _setName($name) {
        $this->_name = $name;
        $this->_querySourceId = 'opal://rdbms/'.$this->_adapter->getServerType().':"'.addslashes($this->_adapter->getDsn()->getConnectionString()).'"/Table:'.$this->_name;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function getSchema() {
        $schema = $this->_introspectSchema();
        $schema->acceptChanges()->isAudited(true);
        
        return $schema;
    }
    
    
// Query source
    public function getQuerySourceId() {
        return $this->_querySourceId;
    }
    
    public function getQuerySourceAdapterHash() {
        return $this->_adapter->getDsnHash();
    }
    
    public function getQuerySourceDisplayName() {
        return $this->_adapter->getDsn()->getDisplayString().'/'.$this->_name;
    }
    
    public function getDelegateQueryAdapter() {
        return $this;
    }
    
    public function supportsQueryType($type) {
        switch($type) {
            case opal\query\IQueryTypes::SELECT:
            case opal\query\IQueryTypes::FETCH:
            case opal\query\IQueryTypes::INSERT:
            case opal\query\IQueryTypes::BATCH_INSERT:
            case opal\query\IQueryTypes::REPLACE:
            case opal\query\IQueryTypes::BATCH_REPLACE:
            case opal\query\IQueryTypes::UPDATE:
            case opal\query\IQueryTypes::DELETE:
                
            case opal\query\IQueryTypes::JOIN:
            case opal\query\IQueryTypes::JOIN_CONSTRAINT:
                
            case opal\query\IQueryTypes::SELECT_ATTACH:
            case opal\query\IQueryTypes::FETCH_ATTACH:
                return true;
                
            default:
                return false;
        }
    }
    
    public function supportsQueryFeature($feature) {
        switch($feature) {
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
    
    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        return false;
    }
    
    
    
// Create
    public function create(opal\rdbms\schema\ISchema $schema, $dropIfExists=false) {
        if($schema->getName() != $this->_name) {
            throw new opal\rdbms\TableNotFoundException(
                'Schema name '.$schema->getName().' does not match table name '.$this->_name, 0, null,
                $this->_adapter->getDsn()->getDatabase(), $this->_name
            );
        }
        
        if($this->exists()) {
            if($dropIfExists) {
                $this->drop();
            } else {
                throw new opal\rdbms\TableConflictException(
                    'Table '.$schema->getName().' already exists', 0, null,
                    $this->_adapter->getDsn()->getDatabase(), $this->_name
                );
            }
        }
        
        $schema->normalize();
        $this->_create($schema);
        
        return $this;
    }
    
    
    protected function _create(opal\rdbms\schema\ISchema $schema) {
        // Table definition
        $sql = 'CREATE';
        
        if($schema->isTemporary()) {
            $sql .= ' TEMPORARY';
        }
        
        $sql .= ' TABLE '.$this->_adapter->quoteIdentifier($schema->getName()).' ('."\n";
        $primaryIndex = $schema->getPrimaryIndex();
        $definitions = array();
        
        
        // Fields
        foreach($schema->getFields() as $name => $field) {
            if(null !== ($def = $this->_generateFieldDefinition($field))) {
                $definitions[] = $def;
            }
        }
        
        
        // Indexes
        foreach($schema->getIndexes() as $index) {
            if($index->isVoid()) {
                throw new opal\schema\RuntimeException(
                    'Index '.$index->getName().' is invalid'
                );
            }
            
            if(null !== ($def = $this->_generateInlineIndexDefinition($index, $primaryIndex))) {
                $definitions[] = $def;
            }
        }


        // Foreign keys
        foreach($schema->getForeignKeys() as $key) {
            if($key->isVoid()) {
                throw new opal\rdbms\schema\IInvalidForeignKey(
                    'Foreign key '.$key->getName().' is invalid'
                );
            }
            
            if(null !== ($def = $this->_generateInlineForeignKeyDefinition($key))) {
                $definitions[] = $def;
            }
        }
        
        
        
        // Flatten definitions
        $sql .= '    '.implode(','."\n".'    ', $definitions)."\n".')'."\n";
        
        
        
        
        // Table options
        $tableOptions = $this->_defineTableOptions($schema);
        
        if(!empty($tableOptions)) {
            $sql .= implode(','."\n", $tableOptions);
        }
        
        $sql = array($sql);
        
        
        // Indexes
        foreach($schema->getIndexes() as $index) {
            if(null !== ($def = $this->_generateStandaloneIndexDefinition($index, $primaryIndex))) {
                $sql[] = $def;
            }
        }
        
        
        // Triggers
        foreach($schema->getTriggers() as $trigger) {
            if(null !== ($def = $this->_generateTriggerDefinition($trigger))) {
                $sql[] = $def;
            }
        }
        
        
        // TODO: stored procedures
        
        
        try {
            foreach($sql as $query) {
                $this->_adapter->prepare($query)->executeRaw();
            }
        } catch(\Exception $e) {
            $this->drop();
            
            throw $e;
        }
        
        return $this;
    }
    
    
// Alter
    public function alter(opal\rdbms\schema\ISchema $schema) {
        $schema->normalize();
        $this->_alter($schema);
        
        return $this;
    }
    
    abstract protected function _alter(opal\rdbms\schema\ISchema $schema);
    
    
// Rename
    public function rename($newName) {
        $sql = 'ALTER TABLE '.$this->_adapter->quoteIdentifier($this->_name).' '.
               'RENAME TO '.$this->_adapter->quoteIdentifier($newName);
               
        $this->_adapter->prepare($sql)->executeRaw();
        $this->_name = $newName;
        
        return $this;
    }
    
    
// Drop
    public function drop() {
        $sql = 'DROP TABLE IF EXISTS '.$this->_adapter->quoteIdentifier($this->_name);
        $this->_adapter->prepare($sql)->executeRaw();
        
        return $this;
    }
    
    
// Lock
    public function lock() {
        return $this->_adapter->lockTable($this->_name);
    }
    
    public function unlock() {
        return $this->_adapter->unlockTable($this->_name);
    }
    
    
// Table options
    protected function _defineTableOptions(opal\rdbms\schema\ISchema $schema) {
        return null;
    }
    
    
// Foreign keys
    protected function _generateInlineForeignKeyDefinition(opal\rdbms\schema\IForeignKey $key) {
        $keySql = 'CONSTRAINT '.$this->_adapter->quoteIdentifier($key->getName()).' FOREIGN KEY';
        $fields = array();
        $references = array();
        
        foreach($key->getReferences() as $reference) {
            $fields[] = $this->_adapter->quoteIdentifier($reference->getField()->getName());
            $references[] = $this->_adapter->quoteIdentifier($reference->getTargetFieldName());
        }
        
        $keySql .= ' ('.implode(',', $fields).')';
        $keySql .= ' REFERENCES '.$this->_adapter->quoteIdentifier($key->getTargetSchema());
        $keySql .= ' ('.implode(',', $references).')';
        
        if(null !== ($action = $key->getDeleteAction())) {
            $action = $this->_normalizeForeignKeyAction($action);
            $keySql .= ' ON DELETE '.$action;
        }
        
        if(null !== ($action = $key->getUpdateAction())) {
            $action = $this->_normalizeForeignKeyAction($action);
            $keySql .= ' ON UPDATE '.$action;
        }
        
        return $keySql;
    }

    protected function _normalizeForeignKeyAction($action) {
        switch($action = strtoupper($action)) {
            case 'RESTRICT':
            case 'CASCADE':
            case 'NO ACTION':
                break;
                
            case 'SET NULL':
            default:
                $action = 'SET NULL';
                break;
        }
        
        return $action;
    }
    

// Triggers
    protected function _generateTriggerDefinition(opal\rdbms\schema\ITrigger $trigger) {
        $triggerSql = 'CREATE TRIGGER '.$this->_adapter->quoteIdentifier($trigger->getName());
        $triggerSql .= $trigger->getTimingName();
        $triggerSql .= ' '.$trigger->getEventName();
        $triggerSql .= ' ON '.$this->_adapter->quoteIdentifier($this->_name);
        $triggerSql .= ' FOR EACH ROW BEGIN '.implode('; ', $trigger->getStatements()).'; END';
        
        return $triggerSql;
    }


// Count
    public function count() {
        $sql = 'SELECT COUNT(*) as total FROM '.$this->_adapter->quoteIdentifier($this->_name);
        $res = $this->_adapter->prepare($sql)->executeRead();
        $row = $res->extract();
        
        return (int)$row['total'];
    }






// Queries
    public function executeSelectQuery(opal\query\ISelectQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null) {
        return $this->_executeReadQuery($query, $keyField, $valField, false);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        $res = $this->_executeReadQuery($query, null, null, false, true);
        $row = $res->getCurrent();
        
        if(isset($row['count'])) {
            return $row['count'];
        }
        
        return 0;
    }
    
    public function executeFetchQuery(opal\query\IFetchQuery $query, opal\query\IField $keyField=null) {
        return $this->_executeReadQuery($query, $keyField, null, true);
    }
    
    public function countFetchQuery(opal\query\IFetchQuery $query) {
        $res = $this->_executeReadQuery($query, null, null, true, true);
        $row = $res->getCurrent();
        
        if(isset($row['count'])) {
            return $row['count'];
        }
        
        return 0;
    }
    
    protected function _executeReadQuery(opal\query\IReadQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null, $forFetch=false, $forCount=false) {
        if($query->getSourceManager()->countSourceAdapters() == 1) {
            // Everything is from the same adapter
            return $this->_executeLocalReadQuery($query, $keyField, $valField, $forFetch, $forCount);
            
        } else {
            // There are remote joins so the output must go through an ArrayManipulator
            return $this->_executeRemoteJoinedReadQuery($query, $keyField, $valField, $forFetch, $forCount);
        }
    }
     
    protected function _executeLocalReadQuery(opal\query\IReadQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null, $forFetch=false, $forCount=false) {
        $stmt = $this->_adapter->prepare('');
        $source = $query->getSource();
        $outFields = array();
        $requiresBatchIterator = false;
        $supportsProcessors = $source->getAdapter()->supportsQueryFeature(opal\query\IQueryFeatures::VALUE_PROCESSOR);
        
        if(!$forCount) {
            $requiresBatchIterator = $forFetch
                                  || !empty($keyField) 
                                  || !empty($valField) 
                                  || $supportsProcessors;
        }
        
        
        
        
        // Attachments
        $attachments = array();
        $attachFields = array();
        
        if(!$forCount && $query instanceof opal\query\IAttachableQuery) {
            $attachments = $query->getAttachments();
            
            if(!empty($attachments)) {
                $requiresBatchIterator = true;
                
                // Get fields that need to be fetched from source for attachment clauses
                foreach($attachments as $attachment) {
                    foreach($attachment->getNonLocalFieldReferences() as $field) {
                        foreach($field->dereference() as $field) {
                            $qName = $field->getQualifiedName();
                            $attachFields[/*$qName*/] = $this->_defineQueryField($field, true, $qName);
                        }
                    }
                }
            }
        }
        
        
        
        // Joins
        $joinSql = null;
        $joinSources = array();
        $joinFields = array();
        
        if($query instanceof opal\query\IJoinProviderQuery) {
            $joins = $query->getJoins();
            
            if(!empty($joins)) {
                // Build join statements
                foreach($joins as $join) {
                    $joinSources[] = $joinSource = $join->getSource();
                    $joinSql .= "\n".$this->_defineQueryJoin($stmt, $join);
                    
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
            
                        $joinFields[/*$fieldAlias*/] = $this->_defineQueryField($field, true, $fieldAlias);
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
            
            $outFields[/*$fieldAlias*/] = $this->_defineQueryField($field, true, $fieldAlias);
        }
        
        
        if(!$forCount) {
            /*
             * We need to create 3 separate arrays in reverse order and then merge them
             * as we have to definitively know if a BatchIterator is required when getting
             * local fields. The only way to know this is to do attachments and joins first.
             */
            $outFields = array_merge($outFields, $joinFields, $attachFields);
        }
        
        
        $stmt->appendSql(
            'SELECT'."\n".'    '.implode(','."\n".'    ', $outFields)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($this->_name).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias()).
            $joinSql
        );
        
        
        $this->_buildWhereClauseSection($stmt, $query);
        $this->_buildGroupSection($stmt, $query);
        $this->_buildHavingClauseSection($stmt, $query);
        
        if($forCount) {
            $stmt->prependSql('SELECT COUNT(*) as count FROM (')->appendSql(') as baseQuery');
        } else {
            $this->_buildOrderSection($stmt, $query);
            $this->_buildLimitSection($stmt, $query);
        }
        
        $res = $stmt->executeRead();
        
        if(!$forCount && $requiresBatchIterator) {
            // We have keyField, valField, attachments and / or value processors or is for fetch
            
            $output = new opal\query\result\BatchIterator($source, $res);
            $output->addSources($joinSources)
                ->isForFetch((bool)$forFetch)
                ->setAttachments($attachments)
                ->setListKeyField($keyField)
                ->setListValueField($valField);
                
            return $output;
        }
        
        return $res;
    }
            
            
    protected function _executeRemoteJoinedReadQuery(opal\query\IReadQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null, $forFetch=false, $forCount=false) {
        $stmt = $this->_adapter->prepare('');        
        $source = $query->getSource();
        $primaryAdapterHash = $source->getAdapterHash();
        
        $outFields = array();
        $aggregateFields = array();
        
        foreach($source->getAllDereferencedFields() as $field) {
            $qName = $field->getQualifiedName();
            
            if($field instanceof opal\query\IAggregateField) {
                /*
                 * Aggregate fields have to be calculated locally in the ArrayManipulator
                 * so don't put them in the outFields list
                 */
                $aggregateFields[$qName] = $field;
            } else {
                $outFields[$qName] = $this->_defineQueryField($field, true, $qName);
            }
        }
        
        
        // Joins & fields
        $remoteJoins = array();
        $localJoins = array();
        $joinSql = null;
        
        foreach($query->getJoins() as $joinSourceAlias => $join) {
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
                    /*
                     * Aggregate fields have to be calculated locally in the ArrayManipulator
                     * so don't put them in the outFields list
                     */
                    $aggregateFields[$qName] = $field;
                } else {
                    $outFields[$qName] = $this->_defineQueryField($field, true, $qName);
                }
            }
            
            $joinSql .= "\n".$this->_defineQueryJoin($stmt, $join);
        }
        

        $stmt->appendSql(
            'SELECT'."\n".'    '.implode(','."\n".'    ', $outFields)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($this->_name).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias()).
            $joinSql
        );
        
        
        
        // Filter results
        if($query instanceof opal\query\IWhereClauseQuery && $query->hasWhereClauses()) {
            $clauseList = $query->getWhereClauseList();
            
            /*
             * We can filter down the results even though the join is processed locally -
             * we just need to only add where clauses that are local to the primary source
             * The main algorithm for finding those clauses is in the clause list class
             */  
            if(null !== ($joinClauseList = $clauseList->getClausesFor($source))) {
                $stmt->appendSql(
                    "\n".'WHERE '.$this->_defineQueryClauseList(
                        $stmt,
                        $joinClauseList
                    )
                );
            }
        }
        
        
        
        /*
         * The following is a means of tentatively ordering and limiting the result set
         * used to create the join. It probably needs a bit of TLC :)
         */
        if(!$forCount) {
            $orderDirectives = array();
            $isPrimaryOrder = true;
            $hasLimit = false;
            $hasOffset = false;
            
            if($query instanceof opal\query\IOrderableQuery) {
                $orderDirectives = $query->getOrderDirectives();
            }
            
            if($query instanceof opal\query\ILimitableQuery) {
                $hasLimit = $query->hasLimit();
            }
            
            if($query instanceof opal\query\IOffsettableQuery) {
                $hasOffset = $query->hasOffset();
            }
            
            if(isset($orderDirectives[0]) 
            && $orderDirectives[0]->getField()->getSourceAlias() != $source->getAlias()) {
                $isPrimaryOrder = false;
            }
            
            if(empty($aggregateFields) && $isPrimaryOrder) {
                // Order
                if(!empty($orderDirectives)) {
                    $orderFields = array();
                    
                    foreach($orderDirectives as $directive) {
                        $field = $directive->getField();
                        
                        if($field->getSourceAlias() != $source->getAlias()) {
                            break;
                        }
                        
                        foreach($field->dereference() as $field) {
                            $orderString = $this->_defineQueryField($field);
                            
                            if($directive->isDescending()) {
                                $orderString .= ' DESC';
                            } else {
                                $orderString .= ' ASC';
                            }
                            
                            $orderFields[] = $orderString;
                        }
                    }
                    
                    $stmt->appendSql("\n".'ORDER BY '.implode(', ', $orderFields));
                }
                
                
                // Limit & offset
                if($hasLimit || $hasOffset) {
                    $limit = null;
                    $offset = null;
                    
                    if($hasLimit) {
                        $limit = $query->getLimit();
                    }
                    
                    if($hasOffset) {
                        $offset = $query->getOffset();
                    }
                        
                    $stmt->appendSql("\n".$this->_defineQueryLimit($limit, $offset));
                }
            }
        }
        
        
        // Fetch result
        $res = $stmt->executeRead();
        $data = $res->toArray();
        
        /*
         * The result will always have to be processed by an ArrayManipulator as there
         * is guaranteed remote data.
         */
        $arrayManipulator = new opal\query\result\ArrayManipulator($source, $data, true);
        return $arrayManipulator->applyRemoteJoinQuery($query, $localJoins, $remoteJoins, $keyField, $valField, $forCount);
    }

    
    
// Insert query
    public function executeInsertQuery(opal\query\IInsertQuery $query) {
        $stmt = $this->_adapter->prepare(
            'INSERT INTO '.$this->_adapter->quoteIdentifier($this->_name)
        );
        
        $fields = array();
        $values = array();
        
        foreach($query->getRow() as $field => $value) {
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

            $stmt->bind($field, $value);
        }
        
        $stmt->appendSql(' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')');
        $stmt->executeWrite();
        
        return $this->_adapter->getLastInsertId();
    }
    
    
// Batch insert query
    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        $stmt = $this->_adapter->prepare(
            'INSERT INTO '.$this->_adapter->quoteIdentifier($this->_name)
        );
        
        $rows = array();
        $fields = array();
        $fieldList = $query->getFields();
        
        foreach($fieldList as $field) {
            $fields[] = $this->_adapter->quoteIdentifier($field);
        }
        
        foreach($query->getRows() as $row) {
            foreach($fieldList as $key) {
                $id = $stmt->generateUniqueKey();
                $value = null;
                
                if(isset($row[$key])) {
                    $value = $row[$key];
                }
                
                $stmt->bind($id, $value);
                $row[$key] = ':'.$id;
            }
            
            $rows[] = '('.implode(',', $row).')';
        }
        
        
        $stmt->appendSql(' ('.implode(',', $fields).') VALUES '.implode(', ', $rows));
        $stmt->executeWrite();
        
        return count($rows);
    }
    
    
// Replace query
    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        $stmt = $this->_adapter->prepare(
            'INSERT INTO '.$this->_adapter->quoteIdentifier($this->_name)
        );
        
        $fields = array();
        $values = array();
        $duplicates = array();
        
        foreach($query->getRow() as $field => $value) {
            $fields[] = $fieldString = $this->_adapter->quoteIdentifier($field);
            $values[] = ':'.$field;
            $duplicates[] = $fieldString.'=VALUES('.$fieldString.')';
            $stmt->bind($field, $value);
        }
        
        $stmt->appendSql(' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')');
        $stmt->appendSql("\n".'ON DUPLICATE KEY UPDATE '.implode(', ', $duplicates));
        $stmt->executeWrite();
        
        return $this->_adapter->getLastInsertId();
    }
    
    
// Batch replace query
    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        $stmt = $this->_adapter->prepare(
            'INSERT INTO '.$this->_adapter->quoteIdentifier($this->_name)
        );
        
        $rows = array();
        $fields = array();
        $fieldList = $query->getFields();
        $duplicates = array();
        
        foreach($fieldList as $field) {
            $fields[] = $fieldString = $this->_adapter->quoteIdentifier($field);
            $duplicates[] = $fieldString.'=VALUES('.$fieldString.')';
        }
        
        foreach($query->getRows() as $row) {
            foreach($fieldList as $key) {
                $id = $stmt->generateUniqueKey();
                $value = null;
                
                if(isset($row[$key])) {
                    $value = $row[$key];
                }
                
                $stmt->bind($id, $value);
                $row[$key] = ':'.$id;
            }
            
            $rows[] = '('.implode(',', $row).')';
        }
        
        
        $stmt->appendSql(' ('.implode(',', $fields).') VALUES '.implode(', ', $rows));
        $stmt->appendSql("\n".'ON DUPLICATE KEY UPDATE '.implode(', ', $duplicates));
        $stmt->executeWrite();
        
        return count($rows);
    }
    
// Update query
    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {
        $stmt = $this->_adapter->prepare(
            'UPDATE '.$this->_adapter->quoteIdentifier($this->_name).' SET'
        );
        
        $values = array();
        
        foreach($query->getValueMap() as $field => $value) {
            // TODO: check for expression
            $id = $stmt->generateUniqueKey();
            $values[] = $this->_adapter->quoteIdentifier($field).' = :'.$id;
            $stmt->bind($id, $value);
        }
        
        $stmt->appendSql("\n    ".implode(','."\n".'    ', $values));
        $this->_buildWhereClauseSection($stmt, $query, null, true);
        
        
        if(!$this->_adapter->supports(opal\rdbms\adapter\Base::UPDATE_LIMIT)) {
            $this->_buildOrderSection($stmt, $query, true);
            $this->_buildLimitSection($stmt, $query, true);
        }

        return $stmt->executeWrite();
    }
    
    
// Delete query
    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {
        $stmt = $this->_adapter->prepare(
            'DELETE FROM '.$this->_adapter->quoteIdentifier($this->_name)
        );
        
        $this->_buildWhereClauseSection($stmt, $query, null, true);
        
        if($this->_adapter->supports(opal\rdbms\adapter\Base::DELETE_LIMIT)) {
            $this->_buildOrderSection($stmt, $query, true);
            $this->_buildLimitSection($stmt, $query, true);
        }

        return $stmt->executeWrite();
    }

    
// Join data
    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        /*
         * This method differs from _executeRemoteJoinedReadQuery() in that this
         * table is considered a remote entity rather than the primary source.
         * We only need to grab rows that match the already defined values in $rows
         */
        $source = $join->getSource();
        $outFields = array();
        
        foreach($join->getSource()->getAllDereferencedFields() as $field) {
            if($field instanceof opal\query\IAggregateField) {
                /*
                 * Aggregates are processed in the controlling source adapter,
                 * usually an ArrayManipulator
                 */
                continue;
            }
            
            $outFields[] = $this->_defineQueryField($field, true, $field->getQualifiedName());
        }
        
        
        $stmt = $this->_adapter->prepare(
            'SELECT'."\n".'    '.implode(','."\n".'    ', $outFields)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($this->_name).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias())
        );
        
        $clauses = $join->getJoinClauseList();
        
        if(!$clauses->isEmpty()) {
            $whereSql = $this->_defineQueryClauseList($stmt, $clauses, $rows);
            
            if(!empty($whereSql)) {
                $stmt->appendSql("\n".'WHERE '.$whereSql);
            }
        }
        
        $res = $stmt->executeRead();
        return $res->toArray();
    }
    
    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        /*
         * This is essentially identical to fetchRemoteJoinData, just responding to
         * rows defined by an attachment rather than join
         */
        $source = $attachment->getSource();
        $outFields = array();
        
        foreach($source->getAllDereferencedFields() as $field) {
            if($field instanceof opal\query\IAggregateField) {
                /*
                 * Aggregates are processed in the controlling source adapter,
                 * usually an ArrayManipulator
                 */
                continue;
            }
            
            $outFields[] = $this->_defineQueryField($field, true, $field->getQualifiedName());
        }
        
        
        $stmt = $this->_adapter->prepare(
            'SELECT'."\n".'    '.implode(','."\n".'    ', $outFields)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($this->_name).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias())
        );
        
        $clauses = $attachment->getJoinClauseList();
        
        if(!$clauses->isEmpty()) {
            $whereSql = $this->_defineQueryClauseList($stmt, $clauses, $rows);
            
            if(!empty($whereSql)) {
                $stmt->appendSql("\n".'WHERE '.$whereSql);
            }
        }
        
        $res = $stmt->executeRead();
        
        /*
         * Here an ArrayManipulator is used as attachments can themselves have joins
         * and attachments. No other processing is done however, as the rest is applied
         * when making completing the original attachment in the parent query executor
         */
        $manipulator = new opal\query\result\ArrayManipulator($source, $res->toArray(), true);
        return $manipulator->applyAttachmentDataQuery($attachment);
    }
    
    
    
// Query fields
    protected function _defineQueryField(opal\query\IField $field, $defineAlias=false, $alias=null) {
        /*
         * This method is used in many places to get a string representation of a 
         * query field. If $defineAlias is true, it is suffixed with AS <alias> and
         * denotes a field in the initial SELECT statement.
         */
        
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
                $targetFieldString = $this->_defineQueryField($field->getTargetField());
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


// Query joins
    protected function _defineQueryJoin(opal\rdbms\IStatement $stmt, opal\query\IJoinQuery $join) {
        switch($join->getType()) {
            case opal\query\IJoinQuery::INNER:
                $output = 'INNER';
                break;
                
            case opal\query\IJoinQuery::LEFT:
                $output = 'LEFT';
                break;
                
            case opal\query\IJoinQuery::RIGHT:
                $output = 'RIGHT';
                break;
        }
        
        $output .= ' JOIN '.$this->_adapter->quoteIdentifier($join->getSource()->getAdapter()->getDelegateQueryAdapter()->getName()).
                   ' AS '.$this->_adapter->quoteTableAliasDefinition($join->getSourceAlias());
                   
        $clauses = $join->getJoinClauseList();
        
        if(!$clauses->isEmpty()) {
            $output .= "\n".'    ON '.$this->_defineQueryClauseList($stmt, $clauses);
        }
        
        return $output;
    }
    
    
// Query clauses
    protected function _buildWhereClauseSection(opal\rdbms\IStatement $stmt, opal\query\IQuery $query, array $remoteJoinData=null, $forUpdateOrDelete=false) {
        if($query instanceof opal\query\IWhereClauseQuery && $query->hasWhereClauses()) {
            $clauses = $query->getWhereClauseList();
            
            if(!$clauses->isEmpty()) {
                $clauseString = $this->_defineQueryClauseList(
                    $stmt,
                    $clauses, 
                    $remoteJoinData, 
                    $forUpdateOrDelete
                );
                
                if(!empty($clauseString)) {
                    $stmt->appendSql(
                        "\n".'WHERE '.$clauseString
                    );
                }
            }
        }
    }
    
    protected function _buildHavingClauseSection(opal\rdbms\IStatement $stmt, opal\query\IQuery $query) {
        if($query instanceof opal\query\IHavingClauseQuery && $query->hasHavingClauses()) {
            $clauses = $query->getHavingClauseList();
            
            if(!$clauses->isEmpty()) {
                $clauseString = $this->_defineQueryClauseList(
                    $stmt, 
                    $clauses
                );
                
                if(!empty($clauseString)) {
                    $stmt->appendSql(
                        "\n".'HAVING '.$clauseString
                    );
                }
            }
        }
    }


    protected function _defineQueryClauseList(opal\rdbms\IStatement $stmt, opal\query\IClauseList $list, array $remoteJoinData=null, $forUpdateOrDelete=false) {
        $output = '';
        
        foreach($list->toArray() as $clause) {
            if($clause instanceof opal\query\IClause) {
                $clauseString = $this->_defineQueryClause(
                    $stmt, $clause, $remoteJoinData, $forUpdateOrDelete
                );
            } else if($clause instanceof opal\query\IClauseList) {
                $clauseString = $this->_defineQueryClauseList(
                    $stmt, $clause, $remoteJoinData, $forUpdateOrDelete
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
    
    protected function _defineQueryClause(opal\rdbms\IStatement $stmt, opal\query\IClause $clause, array $remoteJoinData=null, $forUpdateOrDelete=false) {
        $field = $clause->getField();
        $operator = $clause->getOperator();
        $value = $clause->getPreparedValue();
        
        $isDiscreetAggregate = $field instanceof opal\query\IAggregateField 
                            && $field->hasDiscreetAlias();
        
        if($isDiscreetAggregate) {
            $fieldString = $this->_adapter->quoteFieldAliasReference($field->getAlias());
        } else if($forUpdateOrDelete) {
            /*
             * If used on an aggregate field or for update or delete, the name must
             * be used on some sql servers
             */
            $fieldString = $this->_adapter->quoteFieldAliasReference($field->getName());
        } else {
            $fieldString = $this->_defineQueryField($field);
        }
        
        
        if($value instanceof opal\query\IField && $remoteJoinData !== null) {
            /*
             * If we're defining a clause for a remote join we will be comparing
             * a full dataset, but with an operator defined for a single value
             */
            $qName = $value->getQualifiedName();
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
                case opal\query\clause\Clause::OP_LIKE:
                case opal\query\clause\Clause::OP_CONTAINS:
                case opal\query\clause\Clause::OP_BEGINS:
                case opal\query\clause\Clause::OP_ENDS:
                    // Test using IN operator on data set
                    $operator = opal\query\clause\Clause::OP_IN;
                    $value = array_unique($listData);
                    break;
                    
                case opal\query\clause\Clause::OP_NEQ:
                case opal\query\clause\Clause::OP_NOT_LIKE:
                case opal\query\clause\Clause::OP_NOT_CONTAINS:
                case opal\query\clause\Clause::OP_NOT_BEGINS:
                case opal\query\clause\Clause::OP_NOT_ENDS:
                    // TODO: why is this null again???
                    return null;
                    
                case opal\query\clause\Clause::OP_GT:
                case opal\query\clause\Clause::OP_GTE:
                    // We only need to test against the lowest value
                    $value = min($listData);
                    break;
                    
                case opal\query\clause\Clause::OP_LT:
                case opal\query\clause\Clause::OP_LTE:
                    // We only need to test against the highest value
                    $value = max($listData);
                    break;
                    
                default:
                    throw new opal\query\OperatorException(
                        'Operator '.$operator.' cannot be used for a remote join'
                    );
            }
        }
        
        if($value instanceof opal\query\ISelectQuery) {
            // Subqueries need to be handled separately
            return $this->_defineQueryClauseSubQuery($stmt, $field, $fieldString, $operator, $value);
        } else {
            // Define a standard expression
            return $this->_defineQueryClauseExpression($stmt, $field, $fieldString, $operator, $value);
        }
    }

    protected function _defineQueryClauseSubQuery(opal\rdbms\IStatement $stmt, opal\query\IField $field, $fieldString, $operator, opal\query\ISelectQuery $query) {
        $source = $query->getSource();
        $isSourceLocal = $source->getAdapterHash() == $this->getQuerySourceAdapterHash();
        $hasRemoteSources = $query->getSourceManager()->countSourceAdapters() > 1;
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
            return $this->_defineQueryClauseInlineSubQuery($stmt, $field, $fieldString, $operator, $query);
        } else {
            // We have to execute the subquery and pass the values in manually
            return $this->_defineQueryClauseRemoteSubQuery($stmt, $field, $fieldString, $operator, $query);
        }
    }

    protected function _defineQueryClauseInlineSubQuery(opal\rdbms\IStatement $stmt, opal\query\IField $field, $fieldString, $operator, opal\query\ISelectQuery $query) {
        /*
         * Build a whole separate statement with the subquery and return it
         * in string form as the expression for the clause
         */
        $source = $query->getSource();
        
        if(null === ($targetField = $source->getFirstOutputDataField())) {
            throw new opal\query\ValueException(
                'Clause subquery does not have a distinct return field'
            );
        }
        
        
        $joinSql = null;
        
        foreach($query->getJoins() as $joinSourceAlias => $join) {
            $joinSource = $join->getSource();
            $hash = $joinSource->getAdapterHash();
            $joinSql .= "\n".$this->_defineQueryJoin($stmt, $join);
        }
        
        
        
        
        $adapter = $source->getAdapter();
        
        $stmt2 = $this->_adapter->prepare(
            'SELECT '.$this->_defineQueryField($targetField, true)."\n".
            'FROM '.$this->_adapter->quoteIdentifier($adapter->getDelegateQueryAdapter()->getName()).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias()).
            $joinSql
        );
        
        
        
        $stmt2->setKeyIndex($stmt->getKeyIndex());
        
        
        $this->_buildWhereClauseSection($stmt2, $query);
        $this->_buildGroupSection($stmt2, $query);
        $this->_buildHavingClauseSection($stmt2, $query);
        $this->_buildOrderSection($stmt2, $query);
        
        $limit = $query->getLimit();
        
        switch($operator) {
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_NEQ:
            case opal\query\clause\Clause::OP_LIKE:
            case opal\query\clause\Clause::OP_NOT_LIKE:
                // There can only be one value for this operator..
                $query->limit(1);
                break;
                
            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
            case opal\query\clause\Clause::OP_LT: 
            case opal\query\clause\Clause::OP_LTE:
                // This ensures we check against all possible values
                $operator .= ' ALL';
                break;
        }
        
        
        $this->_buildLimitSection($stmt2, $query);
        $query->limit($limit);
        
        
        /*
         * As the second statement is just a container and not being executed, 
         * we need to pull the bindings out and pass them to the parent
         */
        $stmt->importBindings($stmt2);
        $stmt->setKeyIndex($stmt2->getKeyIndex());
        
        // `myField` = (SELECT FROM ...)
        return $fieldString.' '.strtoupper($operator).' ('."\n    ".str_replace("\n", "\n    ", $stmt2->getSql())."\n".')';
    }

    protected function _defineQueryClauseRemoteSubQuery(opal\rdbms\IStatement $stmt, opal\query\IField $field, $fieldString, $operator, opal\query\ISelectQuery $query) {
        /*
         * Execute the subquery and get the result as a list.
         * Then depending on the operator, build the relevant clause
         */    
        $source = $query->getSource();
        
        if(null === ($targetField = $source->getFirstOutputDataField())) {
            throw new opal\query\ValueException(
                'Clause subquery does not have a distinct return field'
            );
        }
        
        $values = $query->toList($targetField->getName());
        
        switch($operator) {
            // = | IN()
            case opal\query\clause\Clause::OP_EQ:
            case opal\query\clause\Clause::OP_IN:
                if(empty($values)) {
                    return $fieldString.' IS NULL';
                }
                
                return $fieldString.' IN '.$this->_normalizeArrayQueryValue($stmt, $values);
                
            // != | NOT IN()
            case opal\query\clause\Clause::OP_NEQ:
            case opal\query\clause\Clause::OP_NOT_IN:
                if(empty($values)) {
                    return $fieldString.' IS NOT NULL';
                }
                
                return $fieldString.' NOT IN '.$operator.' '.$this->_normalizeArrayQueryValue($stmt, $values);
                
            // > | >=
            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
                return $fieldString.' '.$operator.' '.$this->_normalizeScalarQueryValue($stmt, max($values));
                
                
            // < | <=
            case opal\query\clause\Clause::OP_LT: 
            case opal\query\clause\Clause::OP_LTE:
                return $fieldString.' '.$operator.' '.$this->_normalizeScalarQueryValue($stmt, min($values));
                
                
            // BETWEEN | NOT BETWEEN
            case opal\query\clause\Clause::OP_BETWEEN:
            case opal\query\clause\Clause::OP_NOT_BETWEEN:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not valid for clause subqueries'
                );
                
            
            // LIKE
            case opal\query\clause\Clause::OP_LIKE:
                return $fieldString.' REGEXP '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    core\string\Util::generateLikeMatchRegex($values)
                );
                
            // NOT LIKE
            case opal\query\clause\Clause::OP_NOT_LIKE:
                return $fieldString.' NOT REGEXP '.$this->_normalizeScalarQueryValue(
                    $stmt, 
                    core\string\Util::generateLikeMatchRegex($values)
                );
                
            // LIKE LIST OF %<value>%
            case opal\query\clause\Clause::OP_CONTAINS:
                foreach($values as &$value) {
                    $value = '%'.str_replace(array('_', '%'), array('\_', '\%'), $value).'%';
                }
                
                return $fieldString.' REGEXP '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    core\string\Util::generateLikeMatchRegex($values)
                );
                
            // NOT LIKE LIST OF %<value>%
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
                foreach($values as &$value) {
                    $value = '%'.str_replace(array('_', '%'), array('\_', '\%'), $value).'%';
                }
                
                return $fieldString.' NOT REGEXP '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    core\string\Util::generateLikeMatchRegex($values)
                );
                
            // LIKE LIST OF <value>%
            case opal\query\clause\Clause::OP_BEGINS:
                foreach($values as &$value) {
                    $value = str_replace(array('_', '%'), array('\_', '\%'), $value).'%';
                }
                
                return $fieldString.' REGEXP '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    core\string\Util::generateLikeMatchRegex($values)
                );
                
            // NOT LIKE LIST OF <value>%
            case opal\query\clause\Clause::OP_NOT_BEGINS:
                foreach($values as &$value) {
                    $value = str_replace(array('_', '%'), array('\_', '\%'), $value).'%';
                }
                
                return $fieldString.' NOT REGEXP '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    core\string\Util::generateLikeMatchRegex($values)
                );
                
            // LIKE LIST OF %<value>
            case opal\query\clause\Clause::OP_ENDS:
                foreach($values as &$value) {
                    $value = '%'.str_replace(array('_', '%'), array('\_', '\%'), $value);
                }
                
                return $fieldString.' REGEXP '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    core\string\Util::generateLikeMatchRegex($values)
                );
                
            // NOT LIKE LIST OF %<value>
            case opal\query\clause\Clause::OP_NOT_ENDS:
                foreach($values as &$value) {
                    $value = '%'.str_replace(array('_', '%'), array('\_', '\%'), $value);
                }
                
                return $fieldString.' REGEXP '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    core\string\Util::generateLikeMatchRegex($values)
                );
            
            
            default:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not recognized'
                );
        }
    }

    protected function _defineQueryClauseExpression(opal\rdbms\IStatement $stmt, opal\query\IField $field, $fieldString, $operator, $value) {
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
                
                return $fieldString.' '.$operator.' '.$this->_normalizeScalarQueryValue($stmt, $value);
                
            // > | >= | < | <=
            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
            case opal\query\clause\Clause::OP_LT: 
            case opal\query\clause\Clause::OP_LTE:
                return $fieldString.' '.$operator.' '.$this->_normalizeScalarQueryValue($stmt, $value);
                
            // IN()
            case opal\query\clause\Clause::OP_IN:
                if(empty($value)) {
                    return '1 != 1';
                }
                
                return $fieldString.' IN '.$this->_normalizeArrayQueryValue($stmt, $value);
                
            // NOT IN()
            case opal\query\clause\Clause::OP_NOT_IN:
                if(empty($value)) {
                    return '1 = 1';
                }
                
                return $fieldString.' NOT IN '.$this->_normalizeArrayQueryValue($stmt, $value);
                
            // BETWEEN()
            case opal\query\clause\Clause::OP_BETWEEN:
                return $fieldString.' BETWEEN '.array_shift($value).' AND '.array_shift($value);
                
            // NOT BETWEEN()
            case opal\query\clause\Clause::OP_NOT_BETWEEN:
                return $fieldString.' NOT BETWEEN '.array_shift($value).' AND '.array_shift($value);
            
            // LIKE
            case opal\query\clause\Clause::OP_LIKE:
                return $fieldString.' LIKE '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    str_replace(array('?', '*'), array('_', '%'), $value)
                );
                
            // NOT LIKE
            case opal\query\clause\Clause::OP_NOT_LIKE:
                return $fieldString.' NOT LIKE '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    str_replace(array('?', '*'), array('_', '%'), $value)
                );
                
            // LIKE %<value>%
            case opal\query\clause\Clause::OP_CONTAINS:
                return $fieldString.' LIKE '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    '%'.str_replace(array('_', '%'), array('\_', '\%'), $value).'%'
                );
                
            // NOT LIKE %<value>%
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
                return $fieldString.' NOT LIKE '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    '%'.str_replace(array('_', '%'), array('\_', '\%'), $value).'%'
                );
            
            // LIKE <value>%
            case opal\query\clause\Clause::OP_BEGINS:
                return $fieldString.' LIKE '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    str_replace(array('_', '%'), array('\_', '\%'), $value).'%'
                );
                
            // NOT LIKE <value>%
            case opal\query\clause\Clause::OP_NOT_BEGINS:
                return $fieldString.' NOT LIKE '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    str_replace(array('_', '%'), array('\_', '\%'), $value).'%'
                );
                
            // LIKE %<value>
            case opal\query\clause\Clause::OP_ENDS:
                return $fieldString.' LIKE '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    '%'.str_replace(array('_', '%'), array('\_', '\%'), $value)
                );
                
            // NOT LIKE %<value>
            case opal\query\clause\Clause::OP_NOT_ENDS:
                return $fieldString.' NOT LIKE '.$this->_normalizeScalarQueryValue(
                    $stmt,
                    '%'.str_replace(array('_', '%'), array('\_', '\%'), $value)
                );
            
            
            default:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not recognized'
                );
        }
    }
        
    protected function _normalizeScalarQueryValue(opal\rdbms\IStatement $stmt, $value) {
        /*
         * Convert a clause value into a string to be used in a query, mainly for clauses.
         * If it is an intrinsic value (ie not a field) it should be bound to the statement
         * and the binding key returned instead.
         */
            
        if($value instanceof opal\query\IField) {
            if($value instanceof opal\query\IAggregateField 
            && $value->hasDiscreetAlias()) {
                $valString = $this->_adapter->quoteFieldAliasReference($value->getAlias());
            } else {
                $valString = $this->_defineQueryField($value);
            }
        } else if(is_array($value)) {
            throw new opal\query\ValueException(
                'Expected a scalar as query value, found an array'
            );
        } else {
            $valString = ':'.$stmt->generateUniqueKey();
            $stmt->bind($valString, $value);
        }
        
        return $valString;
    }
    
    protected function _normalizeArrayQueryValue(opal\rdbms\IStatement $stmt, $value) {
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
            $values[] = $this->_normalizeScalarQueryValue($stmt, $val);
        }
        
        return '('.implode(',', $values).')';
    }
            
            
// Groups
    protected function _buildGroupSection(opal\rdbms\IStatement $stmt, opal\query\IQuery $query) {
        if($query instanceof opal\query\IGroupableQuery) {
            $groups = $query->getGroupFields();
            
            if(!empty($groups)) {
                $groupFields = array();
                
                foreach($groups as $field) {
                    $groupFields[] = $this->_defineQueryField($field);
                }
                
                $stmt->appendSql("\n".'GROUP BY '.implode(', ', $groupFields));
            }
        }
    }
    
    
// Order
    protected function _buildOrderSection(opal\rdbms\IStatement $stmt, opal\query\IQuery $query, $forUpdateOrDelete=false) {
        if($query instanceof opal\query\IOrderableQuery) {
            $directives = $query->getOrderDirectives();
            
            if(!empty($directives)) {
                $orderFields = array();
                
                foreach($directives as $directive) {
                    $field = $directive->getField();
                    
                    foreach($field->dereference() as $field) {
                        if($forUpdateOrDelete) {
                            $directiveString = $this->_adapter->quoteIdentifier($field->getName());
                        } else {
                            $directiveString = $this->_defineQueryField($field);
                        }
                        
                        if($directive->isDescending()) {
                            $directiveString .= ' DESC';
                        } else {
                            $directiveString .= ' ASC';
                        }

                        $orderFields[] = $directiveString;
                    }
                }
                
                $stmt->appendSql("\n".'ORDER BY '.implode(', ', $orderFields));
            }
        }
    }
    
    
// Limit
    protected function _buildLimitSection(opal\rdbms\IStatement $stmt, opal\query\IQuery $query, $forUpdateOrDelete=false) {
        if($forUpdateOrDelete) {
            // Some servers cannot deal with offsets in updates / deletes
            if($query instanceof opal\query\ILimitableQuery
            && null !== ($limit = $query->getLimit())) {
                $stmt->appendSql("\n".$this->_defineQueryLimit($limit));
            }
            
            return null;
        }
        
        
        
        $limit = null;
        $offset = null;
        
        if($query instanceof opal\query\ILimitableQuery) {
            $limit = $query->getLimit();
        }
        
        if($query instanceof opal\query\IOffsettableQuery) {
            $offset = $query->getOffset();
        }
            
        if($limit !== null || $offset !== null) {
            $stmt->appendSql("\n".$this->_defineQueryLimit($limit, $offset));
        }
    }
    
    
// Stubs
    abstract protected function _generateFieldDefinition(opal\rdbms\schema\IField $field);
    abstract protected function _generateInlineIndexDefinition(opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex=null);
    abstract protected function _generateStandaloneIndexDefinition(opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex=null);
    abstract protected function _introspectSchema();
    abstract protected function _defineQueryLimit($limit, $offset=null);
    
    
            
// Transaction
    public function beginQueryTransaction() {
        return $this->_adapter->begin();
    }
    
    public function commitQueryTransaction() {
        return $this->_adapter->commit();
    }
    
    public function rollbackQueryTransaction() {
        return $this->_adapter->rollback();
    }
    
    
// Record
    public function newRecord(array $values=null) {
        return new opal\query\record\Base($this, $values);
    }
    
    
    
// Dump
    public function getDumpProperties() {
        return array(
            'adapter' => $this->_adapter->getDsn()->getDisplayString(),
            'name' => $this->_name
        );
    }
}
