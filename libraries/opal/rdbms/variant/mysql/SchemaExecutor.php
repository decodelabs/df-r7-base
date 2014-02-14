<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\mysql;

use df;
use df\core;
use df\opal;
    
class SchemaExecutor extends opal\rdbms\SchemaExecutor {

## Introspect ##
    public function introspect($name) {
        $stmt = $this->_adapter->prepare($sql = 'SHOW TABLE STATUS WHERE Name = :a');
        $stmt->bind('a', $name);
        $res = $stmt->executeRead();
        
        if($res->isEmpty()) {
            throw new opal\rdbms\TableNotFoundException(
                'Table '.$name.' could not be found', 1051, $sql
            );
        }
        
        $status = $res->getCurrent();
        $schema = new Schema($this->_adapter, $name);
        $schema->isAudited(false)
            ->setEngine($status['Engine'])
            ->setRowFormat($status['Row_format'])
            ->setAutoIncrementPosition($status['Auto_increment'])
            ->setCollation($status['Collation'])
            ->setComment($status['Comment']);
        
        
        // Columns
        $sql = 'SHOW FULL COLUMNS FROM '.$this->_adapter->quoteIdentifier($name);
        $res = $this->_adapter->prepare($sql)->executeRead();
        
        foreach($res as $row) {
            if(!preg_match('/^([a-zA-Z_]+)(\((.*)\))?( binary)?( unsigned)?( zerofill)?( character set ([a-z0-9_]+))?$/i', $row['Type'], $matches)) {
                core\stub('Unmatched type', $row);
            }
            
            $type = $matches[1];
            
            if(isset($matches[3])) {
                $args = core\string\Util::parseDelimited($matches[3], ',', '\'');
            } else {
                $args = array();
            }
            
            if($type == 'enum' || $type == 'set') {
                $field = $schema->addField($row['Field'], $type, $args);
            } else {
                array_unshift($args, $row['Field'], $type);
                $field = call_user_func_array(array($schema, 'addField'), $args);
            }
            
            if(isset($matches[5])) {
                if($field instanceof opal\schema\INumericField) {
                    $field->isUnsigned(true);
                } else {
                    throw new opal\rdbms\UnexpectedValueException(
                        'Field '.$field->getName().' is marked as unsigned, but the field type does not support this option'
                    );
                }
            }
            
            if(isset($matches[6])) {
                if($field instanceof opal\schema\INumericField) {
                    $field->shouldZerofill(true);
                } else {
                    throw new opal\rdbms\UnexpectedValueException(
                        'Field '.$field->getName().' is marked as zerofill, but the field type does not support this option'
                    );
                }
            }
            
            if(isset($matches[8])) {
                if($field instanceof opal\schema\ICharacterSetAwareField) {
                    $field->setCharacterSet($matches[8]);
                } else {
                    throw new opal\rdbms\UnexpectedValueException(
                        'Field '.$field->getName().' is marked as having a character set of '.$matches[8].' , but the field type does not support this option'
                    );
                }
            }
            
            $field->isNullable($row['Null'] == 'YES')->setCollation($row['Collation']);
            
            if($row['Default'] == 'CURRENT_TIMESTAMP'
            && $field instanceof opal\schema\IAutoTimestampField) {
                $field->shouldTimestampAsDefault(true);
            } else {
                $field->setDefaultValue($row['Default']);
            }
                
            switch($row['Extra']) {
                case 'auto_increment':
                    if($field instanceof opal\schema\IAutoIncrementableField) {
                        $field->shouldAutoIncrement(true);
                    } else {
                        throw new opal\rdbms\UnexpectedValueException(
                            'Field '.$field->getName().' is marked as auto increment, but the field type does not support this option'
                        );
                    }
                    
                    break;
                    
                case 'on update CURRENT_TIMESTAMP':
                    if($field instanceof opal\schema\IAutoTimestampField) {
                        $field->shouldTimestampOnUpdate(true);
                    } else {
                        throw new opal\rdbms\UnexpectedValueException(
                            'Field '.$field->getName().' is marked to auto timestamp on update, but the field type does not support this option'
                        );
                    }
                    
                    break;
            }
        }
        
        
        // Indexes
        $sql = 'SHOW INDEXES FROM '.$this->_adapter->quoteIdentifier($name);
        $res = $this->_adapter->prepare($sql)->executeRead();
        
        foreach($res as $row) {
            if(!$index = $schema->getIndex($row['Key_name'])) {
                $index = $schema->addIndex($row['Key_name'], false)
                    ->isUnique(!(bool)$row['Non_unique'])
                    ->setIndexType($row['Index_type'])
                    ->setComment(@$row['Index_comment']);
                    
                if($row['Key_name'] == 'PRIMARY') {
                    $schema->setPrimaryIndex($index);
                }
            }
            
            if(!$field = $schema->getField($row['Column_name'])) {
                throw new opal\schema\IndexNotFoundException(
                    'Index field '.$row['Column_name'].' could not be found'
                );
            }
            
            $index->addField($field, $row['Sub_part'], false);
        }
        
        
        
        // Foreign keys
        if($schema->getEngine() == 'InnoDB') {
            $stmt = $this->_adapter->prepare(
                'SELECT * FROM information_schema.REFERENTIAL_CONSTRAINTS '.
                'WHERE CONSTRAINT_SCHEMA = :a && TABLE_NAME = :b'
            );
            
            $stmt->bind('a', $this->_adapter->getDsn()->getDatabase());
            $stmt->bind('b', $name);
            
            $res = $stmt->executeRead();
            
            
            $constraints = array();
            
            foreach($res as $row) {
                $constraints[$row['CONSTRAINT_NAME']] = $row;
            }
            
            
            $stmt = $this->_adapter->prepare(
                'SELECT * FROM information_schema.KEY_COLUMN_USAGE '.
                'WHERE TABLE_SCHEMA = :a && TABLE_NAME = :b && REFERENCED_TABLE_NAME IS NOT NULL'
            );
            
            $stmt->bind('a', $this->_adapter->getDsn()->getDatabase());
            $stmt->bind('b', $name);
            
            $res = $stmt->executeRead();
            
            
            foreach($res as $row) {
                if(!$key = $schema->getForeignKey($row['CONSTRAINT_NAME'])) {
                    $key = $schema->addForeignKey($row['CONSTRAINT_NAME'], $row['REFERENCED_TABLE_NAME']);
                    
                    if(isset($constraints[$row['CONSTRAINT_NAME']])) {
                        $key->setUpdateAction($constraints[$row['CONSTRAINT_NAME']]['UPDATE_RULE'])
                            ->setDeleteAction($constraints[$row['CONSTRAINT_NAME']]['DELETE_RULE']);
                    }
                }
                
                if(!$field = $schema->getField($row['COLUMN_NAME'])) {
                    throw new opal\rdbms\ForeignKeyConflictException(
                        'Foreign key field '.$row['COLUMN_NAME'].' could not be found'
                    );
                }
                
                $key->addReference($field, $row['REFERENCED_COLUMN_NAME']);
            }
        }

        
        // Triggers
        if($this->_adapter->supports(opal\rdbms\adapter\Base::TRIGGERS)) {
            $stmt = $this->_adapter->prepare(
                'SELECT * FROM information_schema.TRIGGERS '.
                'WHERE TRIGGER_SCHEMA = :a && EVENT_OBJECT_TABLE = :b'
            );
            
            $stmt->bind('a', $this->_adapter->getDsn()->getDatabase());
            $stmt->bind('b', $name);
            
            $res = $stmt->executeRead();
            
            foreach($res as $row) {
                $trigger = $schema->addTrigger(
                        $row['TRIGGER_NAME'],
                        $row['EVENT_MANIPULATION'],
                        $row['ACTION_TIMING'],
                        explode(';', $row['ACTION_STATEMENT']) 
                    )
                    ->setCharacterSet($row['CHARACTER_SET_CLIENT'])
                    ->setCollation($row['DATABASE_COLLATION']);
            }
        }
        
        // TODO: add stored procedures
        
        return $schema;
    }



// Fields
    protected function _generateFieldDefinition(opal\rdbms\schema\IField $field) {
        $fieldSql = $this->_adapter->quoteIdentifier($field->getName()).' '.$field->getType();
        
        if($field instanceof opal\schema\IOptionProviderField) {
            $fieldSql .= '('.core\string\Util::implodeDelimited($field->getOptions()).')';
        } else {
            $options = array();
            
            if($field instanceof opal\schema\ILengthRestrictedField
            && null !== ($length = $field->getLength())) {
                $options[] = $length;
            }
            
            if($field instanceof opal\schema\IFloatingPointNumericField) {
                if(null !== ($precision = $field->getPrecision())) {
                    $options[] = $precision;

                    if(null !== ($scale = $field->getScale())) {
                        $options[] = $scale;
                    }
                }
            }
            
            if(!empty($options)) {
                $fieldSql .= '('.implode(',', $options).')';
            }
        }
        
        
        // Field options
        if($field instanceof opal\schema\IBinaryCollationField
        && $field->hasBinaryCollation()) {
            $fieldSql .= ' BINARY';
        }
        
        if($field instanceof opal\schema\ICharacterSetAwareField
        && null !== ($charset = $field->getCharacterSet())) {
            $fieldSql .= ' CHARACTER SET '.$this->_adapter->quoteValue($charset);
        }
        
        if(null !== ($collation = $field->getCollation())) {
            $fieldSql .= ' COLLATE '.$this->_adapter->quoteValue($collation);
        }
        
        if($field instanceof opal\schema\INumericField) {
            if($field->isUnsigned()) {
                $fieldSql .= ' UNSIGNED';
            }
            
            if($field->shouldZerofill()) {
                $fieldSql .= ' ZEROFILL';
            }
        }
        
        if($field->isNullable()) {
            $fieldSql .= ' NULL';
        } else {
            $fieldSql .= ' NOT NULL';
        }
        
        if($field instanceof opal\schema\IAutoTimestampField
        && $field->shouldTimestampAsDefault()) {
            $fieldSql .= ' DEFAULT CURRENT_TIMESTAMP';
        } else if(!$field instanceof opal\rdbms\schema\field\Blob 
        && !$field instanceof opal\rdbms\schema\field\Text
        && null !== ($defaultValue = $field->getDefaultValue())) {
            $fieldSql .= ' DEFAULT '.$this->_adapter->prepareValue($defaultValue, $field);
        }
        
        if($field instanceof opal\schema\IAutoIncrementableField
        && $field->shouldAutoIncrement()) {
            $fieldSql .= ' AUTO_INCREMENT';
        }
        
        if($field instanceof opal\schema\IAutoTimestampField
        && $field->shouldTimestampOnUpdate()) {
            $fieldSql .= ' ON UPDATE CURRENT_TIMESTAMP';
        }
        
        
        if(null !== ($comment = $field->getComment())) {
            $fieldSql .= ' COMMENT '.$this->_adapter->prepareValue($comment);
        }

        return $fieldSql;
    }


// Indexes
    protected function _generateInlineIndexDefinition($tableName, opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex=null) {
        if(null !== ($type = $index->getIndexType())) {
            switch($type = strtoupper($type)) {
                case 'BTREE':
                case 'HASH':
                case 'FULLTEXT':
                case 'SPACIAL':
                    break;
                    
                default:
                    $type = null;
            }
        }
        
        $serverVersion = $this->_adapter->getServerVersion();
        
        if($index === $primaryIndex) {
            $indexSql = 'PRIMARY KEY';
        } else {
            $indexSql = '';
            
            if($index->isUnique()) {
                $indexSql .= 'UNIQUE ';
            } else if($type == 'FULLTEXT' || $type == 'SPACIAL') {
                $indexSQL .= $type.' ';
                $type = null;
            }
            
            $indexSql .= 'INDEX '.$this->_adapter->quoteIdentifier($index->getName());
        }
        
        if($type !== null 
        && $type !== 'FULLTEXT' 
        && $type !== 'SPACIAL') {
            $indexSql .= ' USING '.$type;
        }
        
        $indexFields = array();
        
        foreach($index->getFieldReferences() as $reference) {
            $fieldDef = $this->_adapter->quoteIdentifier($reference->getField()->getName());
            
            if(null !== ($indexSize = $reference->getSize())) {
                $fieldDef .= ' ('.$indexSize.')';
            }
            
            if($reference->isDescending()) {
                $fieldDef .= ' DESC';
            } else {
                $fieldDef .= ' ASC';
            }

            $indexFields[] = $fieldDef;
        }
        
        $indexSql .= ' ('.implode(',', $indexFields).')';
        
        if(version_compare($serverVersion, '5.1.0', '>=')) {
            if(null !== ($blockSize = $index->getKeyBlockSize())) {
                $indexSql .= ' KEY_BLOCK_SIZE '.(int)$blockSize;
            }
            
            if($type === 'FULLTEXT'
            && null !== ($fulltextParser = $index->getFulltextParser())) {
                $indexSql .= ' WITH PARSER '.$this->_adapter->quoteValue($fulltextParser);
            }
            
            if(null !== ($comment = $index->getComment())
            && version_compare($serverVersion, '5.5.0', '>=')) {
                $indexSql .= ' COMMENT '.$this->_adapter->prepareValue($comment);
            }
        }
        
        
        return $indexSql;
    }

    protected function _generateStandaloneIndexDefinition($tableName, opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex=null) {
        return null;
    }


// Foreign keys : see Base


// Triggers
    protected function _generateTriggerDefinition($tableName, opal\rdbms\schema\ITrigger $trigger) {
        switch($trigger->getTimingName()) {
            case 'BEFORE':
            case 'AFTER':
                break;
                
            default:
                throw new opal\rdbms\InvalidArgumentException(
                    'Mysql does not support '.$trigger->getTimingName().' trigger timing'
                );
        }
        
        return parent::_generateTriggerDefinition($tableName, $trigger);
    }


// Table options
    protected function _defineTableOptions(opal\rdbms\schema\ISchema $schema) {
        $sql = array();
        
        foreach($schema->getOptionChanges() as $key => $value) {
            switch($key) {
                case 'engine':
                    if($value !== null) {
                        $sql[] = 'ENGINE '.$value;
                    }
                    
                    break;
                    
                case 'avgRowLength':
                    if($value !== null) {
                        $sql[] = 'AVG_ROW_LENGTH '.(int)$value;
                    }
                    
                    break;
                    
                case 'autoIncrementPosition':
                    if($value !== null) {
                        $sql[] = 'AUTO_INCREMENT '.(int)$value;
                    }
                    
                    break;
                    
                case 'checksum':
                    $sql[] = 'CHECKSUM '.(int)((bool)$value);
                    break;
                    
                case 'characterSet':
                    if($value === null) {
                        $value = 'DEFAULT';
                    }
                    
                    $sql[] = 'CHARACTER SET '.$this->_adapter->prepareValue($value);
                    
                    break;
                    
                case 'collation':
                    if($value !== null) {
                        $sql[] = 'COLLATION '.$this->_adapter->prepareValue($value);
                    }
                    
                    break;
                    
                case 'comment':
                    $sql[] = 'COMMENT '.$this->_adapter->prepareValue($value);
                    break;
                    
                case 'federatedConnection':
                    if($value !== null && $schema->getEngine() == 'FEDERATED') {
                        $sql[] = 'CONNECTION '.$this->_adapter->prepareValue($value);
                    }
                    
                    break;
                    
                case 'dataDirectory':
                    if($value !== null) {
                        $sql[] = 'DATA DIRECTORY '.$this->_adapter->prepareValue($value);
                    }
                    
                    break;
                    
                case 'indexDirectory':
                    if($value !== null) {
                        $sql[] = 'INDEX DIRECTORY '.$this->_adapter->prepareValue($value);
                    }
                    
                    break;
                    
                case 'delayKeyWrite':
                    $sql[] = 'DELAY_KEY_WRITE '.(int)((bool)$value);
                    break;
                    
                case 'keyBlockSize':
                    if(version_compare($this->_adapter->getServerVersion(), '5.1.0', '>=')) {
                        if($value === null || $value < 0) {
                            $value = 0;
                        }
                        
                        $sql[] = 'KEY_BLOCK_SIZE '.(int)$value;
                    } 
                    
                    break;
                    
                case 'maxRows':
                    if($value !== null) {
                        $sql[] = 'MAX_ROWS '.(int)$value;
                    }
                  
                    break;
                  
                case 'minRows':
                    if($value !== null) {
                         $sql[] = 'MIN_ROWS '.(int)$value;
                    }
                  
                    break;
                  
                case 'packKeys':
                    if($value === null) {
                        $value = 'DEFAULT';
                    } else {
                        $value = (int)$value;
                    }
                   
                    $sql[] = 'PACK_KEYS '.$value;
                    break;
                  
                case 'rowFormat':
                    if($value === null) {
                        $value = 'DEFAULT';
                    }
                  
                    $sql[] = 'ROW_FORMAT '.$value;
                    break;
                  
                case 'insertMethod':
                    if($value !== null) {
                        $sql[] = 'INSERT_METHOD '.$value;
                    }
                    
                    break;
                    
                case 'mergeTables':
                    if(!empty($value) && is_array($value)) {
                        foreach($value as &$table) {
                            $table = $this->_adapter->quoteIdentifier($table);
                        }
                        
                        $sql[] = 'UNION ('.implode(', ', $value).')';
                    }
                    
                    break;
            }
        }
        
        return $sql;
    }



## Alter ##
    public function alter($currentName, opal\rdbms\schema\ISchema $schema) {
        $newName = $schema->getName();
        $serverVersion = $this->_adapter->getServerVersion();
        
        $fields = $schema->getFields();
        $removeFields = $schema->getFieldsToRemove();
        $updateFields = $schema->getFieldsToUpdate();
        $addFields = $schema->getFieldsToAdd();
        
        $indexes = $schema->getIndexes();
        $removeIndexes = $schema->getIndexesToRemove();
        $updateIndexes = $schema->getIndexesToUpdate();
        $renameIndexes = $schema->getIndexRenameMap();
        $addIndexes = $schema->getIndexesToAdd();
        $primaryIndex = $schema->getPrimaryIndex();
        
        $keys = $schema->getForeignKeys();
        $renameKeys = $schema->getForeignKeyRenameMap();
        $tempSwapKeys = array();
        $removeKeys = $schema->getForeignKeysToRemove();
        $updateKeys = $schema->getForeignKeysToUpdate();
        $addKeys = $schema->getForeignKeysToAdd();
        
        foreach($updateFields as $field) {
            foreach($keys as $name => $key) {
                if($key->hasField($field)) {
                    unset($updateKeys[$name]);
                    
                    if(isset($renameKeys[$name])) {
                        $name = $renameKeys[$name];
                    }
                    
                    $tempSwapKeys[$name] = $key;
                }
            }
        }
        
        $triggers = $schema->getTriggers();
        
        foreach($triggers as $trigger) {
            if($trigger->hasFieldReference($removeFields)) {
                $schema->removeTrigger($trigger->getName());
            }
        }
        
        $removeTriggers = $schema->getTriggersToRemove();
        $updateTriggers = $schema->getTriggersToUpdate();
        $addTriggers = $schema->getTriggersToAdd();
        
        $sql = array();
        $mainSql = 'ALTER TABLE '.$this->_adapter->quoteIdentifier($currentName);
        
        
        // Remove triggers
        foreach($removeTriggers as $name => $trigger) {
            $sql[] = 'DROP TRIGGER '.$this->_adapter->quoteIdentifier($name);
        }
        
        foreach($updateTriggers as $name => $trigger) {
            $sql[] = 'DROP TRIGGER '.$this->_adapter->quoteIdentifier($name);
        }
        
        
        // Remove keys (to avoid conflicts)
        if(!empty($tempSwapKeys) || !empty($removeKeys)) {
            $swapSql = $mainSql;
            $definitions = array();
            
            foreach($tempSwapKeys as $origName => $key) {
                $definitions[] = 'DROP FOREIGN KEY '.$this->_adapter->quoteIdentifier($origName);
            }
            
            foreach($removeKeys as $name => $key) {
                $definitions[] = 'DROP FOREIGN KEY '.$this->_adapter->quoteIdentifier($name);
            }
            
            $swapSql .= "\n".'    '.implode(','."\n".'    ', $definitions);
            $sql[] = $swapSql;
        }
        
        
        // Table options
        $definitions = $this->_defineTableOptions($schema);
        
        
        // Remove indexes
        foreach($removeIndexes as $name => $index) {
            if($index === $primaryIndex) {
                $definitions[] = 'DROP PRIMARY KEY';
            } else {
                $definitions[] = 'DROP INDEX '.$this->_adapter->quoteIdentifier($name);
            }
        }
        
        foreach($updateIndexes as $name => $index) {
            if($index === $primaryIndex) {
                $definitions[] = 'DROP PRIMARY KEY';
            } else {
                $definitions[] = 'DROP INDEX '.$this->_adapter->quoteIdentifier($name);
            }
        }
        
        
        // Remove fields
        foreach($removeFields as $name => $field) {
            $definitions[] = 'DROP COLUMN '.$this->_adapter->quoteIdentifier($field->getName());
        }
        
        // Update fields
        foreach($updateFields as $name => $field) {
            $definitions[] = 'CHANGE COLUMN '.$this->_adapter->quoteIdentifier($name).' '.$this->_generateFieldDefinition($field);
        }
        
        // Add fields
        $lastField = null;
        
        foreach($fields as $name => $field) {
            if(isset($addFields[$name])) {
                $fieldSql = 'ADD COLUMN '.$this->_generateFieldDefinition($field);
                
                if($lastField === null) {
                    $fieldSql .= ' FIRST';
                } else {
                    $fieldSql .= ' AFTER '.$this->_adapter->quoteIdentifier($lastField->getName());
                }
                
                $definitions[] = $fieldSql;
            }
            
            $lastField = $field;
        }
        
        
        // Add indexes
        foreach($updateIndexes as $name => $index) {
            $definitions[] = 'ADD '.$this->_generateInlineIndexDefinition($newName, $index, $primaryIndex);
        }
        
        foreach($addIndexes as $name => $index) {
            $definitions[] = 'ADD '.$this->_generateInlineIndexDefinition($newName, $index, $primaryIndex);
        }
        
        
        // Add keys
        foreach($tempSwapKeys as $key) {
            $definitions[] = 'ADD '.$this->_generateInlineForeignKeyDefinition($key);
        }
        
        foreach($addKeys as $key) {
            $definitions[] = 'ADD '.$this->_generateInlineForeignKeyDefinition($key);
        }
        
        
        $mainSql .= "\n".'    '.implode(','."\n".'    ', $definitions);
        
        
        $sql[] = $mainSql;
        
        
        // Add triggers
        foreach($updateTriggers as $trigger) {
            $sql[] = $this->_generateTriggerDefinition($newName, $trigger);
        }
        
        foreach($addTriggers as $trigger) {
            $sql[] = $this->_generateTriggerDefinition($newName, $trigger);
        }
        
        foreach($sql as $query) {
            $this->_adapter->prepare($query)->executeRaw();
        }
            
        
        $schema->acceptChanges();
        return $this;
    }
}