<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

use df;
use df\core;
use df\opal;

class Table extends opal\rdbms\Table {
    
// Exists
    public function exists() {
        $stmt = $this->_adapter->prepare('SELECT name FROM sqlite_master WHERE type = :a AND name = :b');
        $stmt->bind('a', 'table');
        $stmt->bind('b', $this->_name);
        $res = $stmt->executeRaw();
        
        return (bool)$res->fetch();
    }
    
// Alter
    protected function _alter(opal\rdbms\schema\ISchema $schema) {
        $this->_adapter->executeSql('PRAGMA foreign_keys=OFF');
        $this->_adapter->begin();
        
        try {
            $backupName = 'backup_'.$this->_name;//.'_'.uniqid();
            
            $addFields = $schema->getFieldsToAdd();
            $renameFields = $schema->getFieldRenameMap();
            $fields = $schema->getFields();
            
            $sourceFields = array();
            $destinationFields = array();
            
            foreach($fields as $name => $field) {
                if(isset($addFields[$name])) {
                    continue;
                }
                
                $destinationFields[] = $this->_adapter->quoteIdentifier($name);
                
                if(isset($renameFields[$name])) {
                    $name = $renameFields[$name];
                }
                
                $sourceFields[] = $this->_adapter->quoteIdentifier($name);
            }
            
            
            // Remove triggers
            $triggers = array();
            
            foreach($schema->getTriggers() as $name => $trigger) {
                $triggers[$name] = $trigger;
                $schema->removeTrigger($name);
                $trigger->_setName($name);
            }

            
            // Create target table
            $schema->acceptChanges()->isAudited(false);
            $schema->_setName($backupName);
            $this->_adapter->createTable($schema);
            $schema->_setName($this->_name);
            
            
            // Copy data
            $sql = 'INSERT INTO '.$this->_adapter->quoteIdentifier($backupName).' '.
                   '('.implode(',', $destinationFields).') '.
                   'SELECT '.implode(',', $sourceFields).' '.
                   'FROM '.$this->_adapter->quoteIdentifier($this->_name);
                   
            $this->_adapter->executeSql($sql);
            
            // Drop original
            $sql = 'DROP TABLE '.$this->_adapter->quoteIdentifier($this->_name);
            $this->_adapter->executeSql($sql);
            
            // Rename target
            $sql = 'ALTER TABLE '.$this->_adapter->quoteIdentifier($backupName).' '.
                   'RENAME TO '.$this->_adapter->quoteIdentifier($this->_name);
                   
            $this->_adapter->executeSql($sql);
           
           
            // Reinstate triggers
            foreach($triggers as $name => $trigger) {
                $schema->populateTrigger($trigger);
                $sql = $this->_generateTriggerDefinition($trigger);
                $this->_adapter->executeSql($sql);
            }
            
            $this->_adapter->commit();
        } catch(\Exception $e) {
            $this->_adapter->rollback();
            $this->_adapter->executeSql('PRAGMA foreign_keys=ON');
            throw $e;
        }
        
        $this->_adapter->executeSql('PRAGMA foreign_keys=ON');
        $this->_adapter->executeSql('VACUUM');
        
        
        return $this;
    }
    
    
// Fields
    protected function _generateFieldDefinition(opal\rdbms\schema\IField $field) {
        $fieldSql = $this->_adapter->quoteIdentifier($field->getName()).' ';
        
        if($field instanceof opal\rdbms\schema\field\Binary
        || $field instanceof opal\rdbms\schema\field\Bit
        || $field instanceof opal\rdbms\schema\field\Char
        || $field instanceof opal\rdbms\schema\field\DateTime
        || $field instanceof opal\rdbms\schema\field\Enum
        || $field instanceof opal\rdbms\schema\field\Set
        || $field instanceof opal\rdbms\schema\field\Text) {
            $fieldSql .= 'TEXT';
        } else 
        if($field instanceof opal\rdbms\schema\field\Blob) {
            $fieldSql .= 'BLOB';
        } else
        if($field instanceof opal\rdbms\schema\field\Float) {
            $fieldSql .= 'REAL';
        } else
        if($field instanceof opal\rdbms\schema\field\Int) {
            $fieldSql .= 'INTEGER';
        } else {
            $fieldSql .= $field->getType();
        }
        
        
        if($field instanceof opal\schema\IOptionProviderField) {
            // do nothing :(
        } else {
            $options = array();
            
            if($field instanceof opal\schema\ILengthRestrictedField
            && null !== ($length = $field->getLength())) {
                $options[] = $length;
            }
            
            if($field instanceof opal\schema\IFloatingPointNumericField
            && null !== ($scale = $field->getScale())) {
                $options[] = $scale;
            }
            
            if(!empty($options)) {
                $fieldSql .= '('.implode(',', $options).')';
            }
        }
        
        if(!$field->isNullable()) {
            $fieldSql .= ' NOT NULL';
            
            if(null !== ($conflictClause = $field->getNullConflictClauseName())) {
                $fieldSql .= ' ON CONFLICT '.$conflictClause;
            }
        }
        
        if($field instanceof opal\schema\IAutoTimestampField
        && $field->shouldTimestampAsDefault()) {
            $fieldSql .= 'DEFAULT (DATETIME(\'now\'))';
        } else if(null !== ($defaultValue = $field->getDefaultValue())) {
            $fieldSql .= ' DEFAULT '.$this->_adapter->prepareValue($defaultValue, $field);
        } else if(!$field->isNullable()) {
            $fieldSql .= ' DEFAULT '.$this->_adapter->prepareValue('', $field);
        }
        
        if(null !== ($collation = $field->getCollation())) {
            $fieldSql .= ' COLLATE '.$this->_adapter->quoteValue($collation);
        }
        
        return $fieldSql;
    }
    
// Indexes
    protected function _generateInlineIndexDefinition(opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex=null) {
        if(!$index->isUnique()) {
            return null;
        }
        
        $indexSql = 'CONSTRAINT '.$this->_adapter->quoteIdentifier($index->getName());
        
        if($index === $primaryIndex) {
            $indexSql .= ' PRIMARY KEY';
        } else {
            $indexSql .= ' UNIQUE';
        }
        
        $indexFields = array();
        
        foreach($index->getFieldReferences() as $reference) {
            $fieldDef = $this->_adapter->quoteIdentifier($reference->getField()->getName());
            
            if($reference->isDescending()) {
                $fieldDef .= ' DESC';
            } else {
                $fieldDef .= ' ASC';
            }

            $indexFields[] = $fieldDef;
        }
        
        $indexSql .= ' ('.implode(',', $indexFields).')';
        
        if(null !== ($conflictClause = $index->getConflictClauseName())) {
            $indexSql .= ' ON CONFLICT '.$conflictClause;
        }
        
        return $indexSql;
    }
    
    protected function _generateStandaloneIndexDefinition(opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex=null) {
        if($index->isUnique()) {
            return null;
        }
        
        $indexSql = 'CREATE INDEX '.$this->_adapter->quoteIdentifier($index->getName());
        $indexSql .= ' ON '.$this->_adapter->quoteIdentifier($this->_name);
        
        $indexFields = array();
        
        foreach($index->getFieldReferences() as $reference) {
            $fieldDef = $this->_adapter->quoteIdentifier($reference->getField()->getName());
            
            if($reference->isDescending()) {
                $fieldDef .= ' DESC';
            } else {
                $fieldDef .= ' ASC';
            }
            
            $indexFields[] = $fieldDef;
        }
        
        $indexSql .= ' ('.implode(',', $indexFields).')';
        
        return $indexSql;
    }
    
    
// Foreign keys
    protected function _generateInlineForeignKeyDefinition(opal\rdbms\schema\IForeignKey $key) {
        $keySql = parent::_generateInlineForeignKeyDefinition($key);
        
        // TODO: add MATCH clause
        // TODO: add DEFERRABLE clause
        
        return $keySql;
    }
    
    protected function _normalizeForeignKeyAction($action) {
        switch($action = strtoupper($action)) {
            case 'RESTRICT':
            case 'CASCADE':
            case 'NO ACTION':
            case 'SET DEFAULT':
                break;
                
            case 'SET NULL':
            default:
                $action = 'SET NULL';
                break;
        }
        
        return $action;
    }
    
    
// Table options : see Base
    
// Triggers
    protected function _generateTriggerDefinition(opal\rdbms\schema\ITrigger $trigger) {
        $triggerSql = 'CREATE';
        
        if($trigger->isTemporary()) {
            $triggerSql .= ' TEMPORARY';
        }
        
        $triggerSql .= ' TRIGGER '.$this->_adapter->quoteIdentifier($trigger->getName());
        $triggerSql .= ' '.$trigger->getTimingName();
        $triggerSql .= ' '.$trigger->getEventName();
        
        if($trigger->getEvent() == opal\rdbms\schema\constraint\Trigger::UPDATE) {
            $updateFields = $trigger->getUpdateFields();
            
            if(!empty($updateFields)) {
                foreach($updateFields as &$field) {
                    $field = $this->_adapter->quoteIdentifier($field);
                }
                
                $triggerSql .= ' OF '.implode(', ', $updateFields);
            }
        }
        
        $triggerSql .= ' ON '.$this->_adapter->quoteIdentifier($this->_name);
        $triggerSql .= ' FOR EACH ROW';
        
        if(null !== ($trigger->getWhenExpression())) {
            $triggerSql .= ' WHEN '.$trigger->getWhenExpression();
        }
        
        $triggerSql .= ' BEGIN '.implode('; ',$trigger->getStatements()).'; END';
        
        return $triggerSql;
    }
    
    
// Rename: see Base
// Drop: see Base
    
    
// Truncate
    public function truncate() {
        $sql = 'DELETE FROM '.$this->_adapter->quoteIdentifier($this->_name);
        $this->_adapter->executeSql($sql);
        $this->_adapter->executeSql('VACUUM');
        
        return $this;
    }
    
// Introspect
    protected function _introspectSchema() {
        $stmt = $this->_adapter->prepare('SELECT * FROM sqlite_master WHERE tbl_name = :a');
        $stmt->bind('a', $this->_name);
        
        $res = $stmt->executeRead();
        
        if($res->isEmpty()) {
            throw new opal\rdbms\TableNotFoundException(
                'Table '.$this->_name.' could not be found', 1, $sql
            );
        }
        
        $triggers = array();
        $indexes = array();
        $tableData = null;
        
        while(!$res->isEmpty()) {
            $row = $res->extract();
            
            switch($row['type']) {
                case 'table':
                    $tableData = $row;
                    break;
                    
                case 'index':
                    if(!empty($row['sql'])) {
                        $indexes[$row['name']] = $row;
                    }
                    
                    break;
                    
                case 'trigger':
                    $triggers[$row['name']] = $row;
                    break;
            }
        }

        
        if(empty($tableData) || empty($tableData['sql'])) {
            throw new opal\rdbms\TableNotFoundException(
                'Table '.$this->_name.' could not be found', 1, $sql
            );
        }
        
        $schema = new Schema($this->_adapter, $this->_name);
        $schema->isAudited(false);
        
        $parts = explode('(', substr($tableData['sql'], 0, -1), 2);
        $createSql = trim(array_shift($parts));
        $defSql = trim(preg_replace("/\s+/", ' ', array_shift($parts)));
        
        if(preg_match('/TEMPORARY/i', $createSql)) {
            $schema->isTemporary(true);
        }
        
        $defs = preg_split('/,[^0-9]/', $defSql);
        
        foreach($defs as $def) {
            if(preg_match('/^[`"]?([^"`]+)[`"]? ([a-zA-Z]+)( )?(\((.*)\))?/i', $def, $matches)) {
                // Field
                $args = isset($matches[5]) ?
                    core\string\Util::parseDelimited($matches[5], ',', '\'') 
                    : array();
                
                array_unshift($args, $matches[1], $matches[2]);
                $field = call_user_func_array(array($schema, 'addField'), $args);
                
                if(preg_match('/ NOT NULL( ON CONFLICT (ROLLBACK|ABORT|FAIL|IGNORE|REPLACE))?/i', $def, $matches)) {
                    $field->isNullable(false);
                    
                    if(!empty($matches[2])) {
                        $field->setNullConflictClause($matches[2]);
                    }
                } else {
                    $field->isNullable(true);
                }
                
                if(preg_match('/ UNSIGNED/i', $def)) {
                    $field->isUnsigned(true);
                }
                
                if(preg_match('/ DEFAULT ([\'](.*)[\']|([\d]+))/i', $def, $matches)) {
                    if(isset($matches[3])) {
                        $field->setDefaultValue($matches[3]);
                    } else {
                        $field->setDefaultValue($matches[2]);
                    }
                }
                
                
                if(preg_match('/ (PRIMARY KEY|UNIQUE|REFERENCES) /i', $def, $matches)) {
                    $type = strtoupper($matches[2]);
                    
                    switch($type) {
                        // Column indexes
                        case 'PRIMARY KEY':
                        case 'UNIQUE':
                            $index = $schema->addUniqueIndex($field->getName(), $field);
                            
                            if($type === 'PRIMARY KEY') {
                                $schema->setPrimaryIndex($index);
                            }
                            
                            if(preg_match('/ON CONFLICT (ROLLBACK|ABORT|FAIL|IGNORE|REPLACE)/i', $def, $matches)) {
                                $index->setConflictClause($matches[1]);
                            }
                            
                            break;
                            
                        // Foreign keys
                        case 'FOREIGN KEY':
                            if(!preg_match('/REFERENCES ["`]?([^"`]+)["`]?( \((.*)\))?( ON DELETE (SET NULL|SET DEFAULT|CASCADE|RESTRICT|NO ACTION))?( ON UPDATE (SET NULL|SET DEFAULT|CASCADE|RESTRICT|NO ACTION))?( (NOT )? DEFERRABLE( INITIALLY (DEFERRED|IMMEDIATE)))?/i', $def, $matches)) {
                                throw new opal\rdbms\ForeignKeyConflictException(
                                    'Unmatched foreign key: '.$def
                                );
                            }
                            
                            $key = $schema->addForeignKey($field->getName(), $matches[1]);
                            $references = core\string\Util::parseDelimited($matches[3], ',', '"`');
                            
                            $key->addReference($field, array_shift($references));
                            
                            if(isset($matches[4])) {
                                $key->setDeleteAction($matches[5]);
                            }
                            
                            if(isset($matches[6])) {
                                $key->setUpdateAction($matches[7]);
                            }
                            
                            break;
                    }
                }
                
            } else if(preg_match('/^CONSTRAINT [`"]?([^"`]+)["`]? (PRIMARY KEY|FOREIGN KEY|UNIQUE)/i', $def, $matches)) {
                $type = strtoupper($matches[2]);
                
                switch($type) {
                    // Inline indexes
                    case 'PRIMARY KEY':
                    case 'UNIQUE':
                        $index = $schema->addUniqeIndex($matches[1], array());
                        preg_match('/'.$type.' \((.*)\)/i', $def, $matches);
                        
                        foreach(core\string\Util::parseDelimited($matches[1], ',', null) as $part) {
                            $temp = explode(' ', trim($part), 2);
                            $fieldName = trim(array_shift($temp), '`\' ');
                            $isDescending = strtoupper(trim(array_shift($temp))) == 'DESC';
                            
                            if(!$field = $schema->getField($fieldName)) {
                                throw new opal\schema\IndexNotFoundException(
                                    'Index field '.$fieldName.' could not be found'
                                );
                            }
                            
                            $index->addField($field, null, $isDescending);
                        }
                        
                        if($type === 'PRIMARY KEY') {
                            $schema->setPrimaryIndex($index);
                        }
                        
                        if(preg_match('/ON CONFLICT (ROLLBACK|ABORT|FAIL|IGNORE|REPLACE)/i', $def, $matches)) {
                            $index->setConflictClause($matches[1]);
                        }
                        
                        break;
                        
                    // Foreign keys
                    case 'FOREIGN KEY':
                        $name = $matches[1];
                        
                        if(!preg_match('/FOREIGN KEY \((.*)\) REFERENCES ["`]?([^"`]+)["`]?( \((.*)\))?( ON DELETE (SET NULL|SET DEFAULT|CASCADE|RESTRICT|NO ACTION))?( ON UPDATE (SET NULL|SET DEFAULT|CASCADE|RESTRICT|NO ACTION))?( (NOT )? DEFERRABLE( INITIALLY (DEFERRED|IMMEDIATE)))?$/i', $def, $matches)) {
                            throw new opal\rdbms\ForeignKeyConflictException(
                                'Unmatched foreign key: '.$def
                            );
                        }
                        
                        $key = $schema->addForeignKey($name, $matches[2]);
                        $fields = core\string\Util::parseDelimited($matches[1], ',', '"`');
                        
                        $references = core\string\Util::parseDelimited($matches[4], ',', '"`');
                        
                        foreach($fields as $fieldName) {
                            if(!$field = $schema->getField($fieldName)) {
                                throw new opal\rdbms\ForeignKeyConflictException(
                                    'Foreign key field '.$fieldName.' could not be found'
                                );
                            }
                            
                            $key->addReference($field, array_shift($references));
                        }
                        
                        if(isset($matches[5])) {
                            $key->setDeleteAction($matches[6]);
                        }
                        
                        if(isset($matches[7])) {
                            $key->setUpdateAction($matches[8]);
                        }
                        
                        break;
                }
            }
        }


        // Standalone indexes
        foreach($indexes as $name => $indexData) {
            $index = $schema->addIndex($name, array());
            
            if(!preg_match('/INDEX ["`]?'.$indexData['name'].'["`]? ON ["`]?'.$indexData['tbl_name'].'["`]? \((.*)\)/i', $indexData['sql'], $matches)) {
                throw new opal\schema\UnexpectedValueException(
                    'Unmatched index: '.$indexData['sql']
                );
            }
            
            foreach(core\string\Util::parseDelimited($matches[1], ',', null) as $part) {
                $temp = explode(' ', trim($part), 2);
                $fieldName = trim(array_shift($temp), '`" ');
                $isDescending = strtoupper(trim(array_shift($temp))) == 'DESC';
                
                if(!$field = $schema->getField($fieldName)) {
                    throw new opal\schema\IndexNotFoundException(
                        'Index field '.$fieldName.' could not be found'
                    );
                }
                
                $index->addField($field, null, $isDescending);
            }
        }

        
        // Triggers
        foreach($triggers as $name => $triggerData) {
            if(!preg_match('/^CREATE (TEMP|TEMPORARY )?TRIGGER (IF NOT EXISTS )?["`]?'.$name.'["`]?( (BEFORE|AFTER|INSTEAD OF))? (DELETE|INSERT|UPDATE)( OF (.*))? ON ["`]?'.$this->_name.'["`]?( FOR EACH ROW)?( WHEN (.*))? BEGIN (.*) END$/i', $triggerData['sql'], $matches)) {
                throw new opal\rdbms\UnexpectedValueException(
                    'Unmatched trigger', 0, $triggerData['sql']
                );
            }
            
            $trigger = $schema->addTrigger(
                    $name,
                    $matches[5],
                    strtoupper($matches[4]),
                    explode(';', $matches[11])
                )
                ->isTemporary(!empty($matches[1]));
                
            if(!empty($matches[7])) {
                $cols = core\string\Util::parseDelimited($matches[7], ',', '`"');
                array_map('trim', $cols);
                
                $trigger->setUpdateFields($cols);
            }
            
            if(!empty($matches[10])) {
                $trigger->setWhenExpression($matches[10]);
            }
        }


        // TODO: add stored procedures
        
        return $schema;
    }
    
    
// Replace query
    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        core\stub($query);
    }
    
// Batch replace query
    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        core\stub($query);
    }
    
    
// Query clauses
    protected function _defineQueryClauseInlineSubQuery(opal\rdbms\IStatement $stmt, opal\query\IField $field, $fieldString, $operator, opal\query\ISelectQuery $query) {
        $source = $query->getSource();
        $targetField = null;
        
        foreach($source->getOutputFields() as $alias => $field) {
            if($field instanceof opal\query\IWildcardField) {
                continue;
            }
            
            $targetField = $field;
            break;
        }
        
        if($targetField === null) {
            throw new opal\query\ValueException(
                'Clause subquery does not have a distinct return field'
            );
        }
        
        
        switch($operator) {
            case opal\query\clause\Clause::OP_GT:
            case opal\query\clause\Clause::OP_GTE:
                $targetFieldDef = 'MAX('.$this->_defineQueryField($targetField).')';
                break;
                
            case opal\query\clause\Clause::OP_LT: 
            case opal\query\clause\Clause::OP_LTE:
                $targetFieldDef = 'MIN('.$this->_defineQueryField($targetField).')';
                break;
                
            default:
                $targetFieldDef = $this->_defineQueryField($targetField, true);
        }
        
        
        $stmt2 = $this->_adapter->prepare(
            'SELECT '.$targetFieldDef."\n".
            'FROM '.$this->_adapter->quoteIdentifier($this->_name).' '.
            'AS '.$this->_adapter->quoteTableAliasDefinition($source->getAlias())
        );
        
        
        $this->_buildWhereClauseSection($stmt2, $query);
        $this->_buildGroupSection($stmt2, $query);
        $this->_buildHavingClauseSection($stmt2, $query);
        $this->_buildOrderSection($stmt2, $query);
        $this->_buildLimitSection($stmt2, $query);
        
        $stmt->importBindings($stmt2);
        return $fieldString.' '.$operator.' ('."\n    ".str_replace("\n", "\n    ", $stmt2->getSql())."\n".')';
    }
    
    
// Query limit
    protected function _defineQueryLimit($limit, $offset=null) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        if($offset < 0) {
            $offset = 0;
        }
        
        if($offset > 0 && $limit == 0) {
            $limit = '18446744073709551615';
        }
        
        
        if($limit > 0) {
            $output = 'LIMIT '.$limit;
            
            if($offset > 0) {
                $output .= ' OFFSET '.$offset;
            }
            
            return $output;
        }
    }
    
    
// Insert
    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        $stmt = $this->_adapter->prepare(
            'INSERT INTO '.$this->_adapter->quoteIdentifier($this->_name)
        );
        
        $fields = $bindValues = $query->getFields();
        $stmt->appendSql(' ('.implode(',', $fields).') VALUES ');
        
        foreach($bindValues as &$field) {
            $field = ':'.$field;
        }
        
        $stmt->appendSql('('.implode(',', $bindValues).')');
        
        $rows = array();
        $output = 0;
        
        foreach($query->getRows() as $row) {
            foreach($row as $key => $value) {
                $stmt->bind($key, $value);
            }
            
            $output += $stmt->executeWrite();
        }
        
        return $output;
    }
}
