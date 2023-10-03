<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\variant\mysql;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;

use df\flex;
use df\opal;

class SchemaExecutor extends opal\rdbms\SchemaExecutor
{
    ## Stats ##
    public function getTableStats($name)
    {
        $stmt = $this->_adapter->prepare($sql = 'SHOW TABLE STATUS WHERE Name = :a');
        $stmt->bind('a', $name);
        $res = $stmt->executeRead();

        if ($res->isEmpty()) {
            throw Exceptional::{'df/opal/rdbms/TableNotFound,NotFound'}(
                'Table ' . $name . ' could not be found',
                [
                    'code' => 1051,
                    'data' => [
                        'sql' => $sql
                    ]
                ]
            );
        }

        $status = $res->getCurrent();
        $output = new opal\rdbms\TableStats();

        $count = $this->_adapter->prepare('SELECT COUNT(*) as count FROM ' . $this->_adapter->quoteIdentifier($name))
            ->executeRead()
            ->getCurrent()['count'];

        $output
            ->setVersion($status['Version'])
            ->setRowCount($count)
            ->setSize($status['Data_length'])
            ->setIndexSize($status['Index_length'])
            ->setCreationDate($status['Create_time'])
            ->setSchemaUpdateDate($status['Update_time'])
            ->setAttributes($status);

        return $output;
    }


    ## Introspect ##
    public function introspect($name)
    {
        $stmt = $this->_adapter->prepare($sql = 'SHOW TABLE STATUS WHERE Name = :a');
        $stmt->bind('a', $name);
        $res = $stmt->executeRead();

        if ($res->isEmpty()) {
            throw Exceptional::{'df/opal/rdbms/TableNotFound,NotFound'}(
                'Table ' . $name . ' could not be found',
                [
                    'code' => 1051,
                    'data' => [
                        'sql' => $sql
                    ]
                ]
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
        $sql = 'SHOW FULL COLUMNS FROM ' . $this->_adapter->quoteIdentifier($name);
        $res = $this->_adapter->prepare($sql)->executeRead();

        foreach ($res as $row) {
            if (!preg_match('/^([a-zA-Z_]+)(\((.*)\))?( binary)?( unsigned)?( zerofill)?( character set ([a-z0-9_]+))?$/i', (string)$row['Type'], $matches)) {
                Glitch::incomplete(['Unmatched type', $row]);
            }

            $type = $matches[1];

            if (isset($matches[3])) {
                $args = flex\Delimited::parse($matches[3], ',', '\'');
            } else {
                $args = [];
            }

            if ($type == 'enum' || $type == 'set') {
                $field = $schema->addField($row['Field'], $type, $args);
            } else {
                $field = $schema->addField($row['Field'], $type, ...$args);
            }

            if (isset($matches[5])) {
                if ($field instanceof opal\schema\INumericField) {
                    $field->isUnsigned(true);
                } else {
                    throw Exceptional::UnexpectedValue(
                        'Field ' . $field->getName() . ' is marked as unsigned, but the field type does not support this option'
                    );
                }
            }

            if (isset($matches[6])) {
                if ($field instanceof opal\schema\INumericField) {
                    $field->shouldZerofill(true);
                } else {
                    throw Exceptional::UnexpectedValue(
                        'Field ' . $field->getName() . ' is marked as zerofill, but the field type does not support this option'
                    );
                }
            }

            if (isset($matches[8])) {
                if ($field instanceof opal\schema\ICharacterSetAwareField) {
                    $field->setCharacterSet($matches[8]);
                } else {
                    throw Exceptional::UnexpectedValue(
                        'Field ' . $field->getName() . ' is marked as having a character set of ' . $matches[8] . ' , but the field type does not support this option'
                    );
                }
            }

            $field->isNullable($row['Null'] == 'YES')->setCollation($row['Collation']);

            if ($row['Default'] == 'CURRENT_TIMESTAMP'
            && $field instanceof opal\schema\IAutoTimestampField) {
                $field->shouldTimestampAsDefault(true);
            } else {
                $field->setDefaultValue($row['Default']);
            }

            switch ($row['Extra']) {
                case 'auto_increment':
                    if ($field instanceof opal\schema\IAutoIncrementableField) {
                        $field->shouldAutoIncrement(true);
                    } else {
                        throw Exceptional::UnexpectedValue(
                            'Field ' . $field->getName() . ' is marked as auto increment, but the field type does not support this option'
                        );
                    }

                    break;

                case 'on update CURRENT_TIMESTAMP':
                    if ($field instanceof opal\schema\IAutoTimestampField) {
                        $field->shouldTimestampOnUpdate(true);
                    } else {
                        throw Exceptional::UnexpectedValue(
                            'Field ' . $field->getName() . ' is marked to auto timestamp on update, but the field type does not support this option'
                        );
                    }

                    break;
            }
        }


        // Indexes
        $sql = 'SHOW INDEXES FROM ' . $this->_adapter->quoteIdentifier($name);
        $res = $this->_adapter->prepare($sql)->executeRead();

        foreach ($res as $row) {
            if (!$index = $schema->getIndex($row['Key_name'])) {
                $index = $schema->addIndex($row['Key_name'], false)
                    ->isUnique(!(bool)$row['Non_unique'])
                    ->setIndexType($row['Index_type'])
                    ->setComment(@$row['Index_comment']);

                if ($row['Key_name'] == 'PRIMARY') {
                    $schema->setPrimaryIndex($index);
                }
            }

            if (!$field = $schema->getField($row['Column_name'])) {
                throw Exceptional::{'df/opal/rdbms/IndexNotFound,NotFound'}(
                    'Index field ' . $row['Column_name'] . ' could not be found'
                );
            }

            $index->addField($field, $row['Sub_part'], false);
        }



        // Foreign keys
        if ($schema->getEngine() == 'InnoDB') {
            $stmt = $this->_adapter->prepare(
                'SELECT * FROM information_schema.REFERENTIAL_CONSTRAINTS ' .
                'WHERE CONSTRAINT_SCHEMA = :a && TABLE_NAME = :b'
            );

            $stmt->bind('a', $this->_adapter->getDsn()->getDatabase());
            $stmt->bind('b', $name);

            $res = $stmt->executeRead();


            $constraints = [];

            foreach ($res as $row) {
                $constraints[$row['CONSTRAINT_NAME']] = $row;
            }


            $stmt = $this->_adapter->prepare(
                'SELECT * FROM information_schema.KEY_COLUMN_USAGE ' .
                'WHERE TABLE_SCHEMA = :a && TABLE_NAME = :b && REFERENCED_TABLE_NAME IS NOT NULL'
            );

            $stmt->bind('a', $this->_adapter->getDsn()->getDatabase());
            $stmt->bind('b', $name);

            $res = $stmt->executeRead();


            foreach ($res as $row) {
                if (!$key = $schema->getForeignKey($row['CONSTRAINT_NAME'])) {
                    $key = $schema->addForeignKey($row['CONSTRAINT_NAME'], $row['REFERENCED_TABLE_NAME']);

                    if (isset($constraints[$row['CONSTRAINT_NAME']])) {
                        $key->setUpdateAction($constraints[$row['CONSTRAINT_NAME']]['UPDATE_RULE'])
                            ->setDeleteAction($constraints[$row['CONSTRAINT_NAME']]['DELETE_RULE']);
                    }
                }

                if (!$field = $schema->getField($row['COLUMN_NAME'])) {
                    throw Exceptional::{'df/opal/rdbms/ForeignKeyConflict'}(
                        'Foreign key field ' . $row['COLUMN_NAME'] . ' could not be found'
                    );
                }

                $key->addReference($field, $row['REFERENCED_COLUMN_NAME']);
            }
        }


        // Triggers
        if ($this->_adapter->supports(opal\rdbms\adapter\Base::TRIGGERS)) {
            $stmt = $this->_adapter->prepare(
                'SELECT * FROM information_schema.TRIGGERS ' .
                'WHERE TRIGGER_SCHEMA = :a && EVENT_OBJECT_TABLE = :b'
            );

            $stmt->bind('a', $this->_adapter->getDsn()->getDatabase());
            $stmt->bind('b', $name);

            $res = $stmt->executeRead();

            foreach ($res as $row) {
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





    // Table
    protected function _generateTableOptions(opal\rdbms\schema\ISchema $schema)
    {
        return ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }


    // Fields
    protected function _generateFieldDefinition(opal\rdbms\schema\IField $field)
    {
        $fieldSql = $this->_adapter->quoteIdentifier($field->getName()) . ' ' . $field->getType();

        if ($field instanceof opal\schema\IOptionProviderField) {
            $fieldSql .= '(' . flex\Delimited::implode($field->getOptions()) . ')';
        } else {
            $options = [];

            if ($field instanceof opal\schema\ILengthRestrictedField
            && null !== ($length = $field->getLength())) {
                $options[] = $length;
            }

            if ($field instanceof opal\schema\IFloatingPointNumericField) {
                if (null !== ($precision = $field->getPrecision())) {
                    $options[] = $precision;

                    if (null !== ($scale = $field->getScale())) {
                        $options[] = $scale;
                    }
                }
            }

            if (!empty($options)) {
                $fieldSql .= '(' . implode(',', $options) . ')';
            }
        }


        // Field options
        if ($field instanceof opal\schema\IBinaryCollationField
        && $field->hasBinaryCollation()) {
            $fieldSql .= ' BINARY';
        }

        if ($field instanceof opal\schema\ICharacterSetAwareField
        && null !== ($charset = $field->getCharacterSet())) {
            $fieldSql .= ' CHARACTER SET ' . $this->_adapter->quoteValue($charset);
        }

        if (null !== ($collation = $field->getCollation())) {
            $fieldSql .= ' COLLATE ' . $this->_adapter->quoteValue($collation);
        }

        if ($field instanceof opal\schema\INumericField) {
            if ($field->isUnsigned()) {
                $fieldSql .= ' UNSIGNED';
            }

            if ($field->shouldZerofill()) {
                $fieldSql .= ' ZEROFILL';
            }
        }

        if ($field->isNullable()) {
            $fieldSql .= ' NULL';
        } else {
            $fieldSql .= ' NOT NULL';
        }

        if ($field instanceof opal\schema\IAutoTimestampField
        && $field->shouldTimestampAsDefault()) {
            $fieldSql .= ' DEFAULT CURRENT_TIMESTAMP';
        } elseif (!$field instanceof opal\rdbms\schema\field\Blob
        && !$field instanceof opal\rdbms\schema\field\Text
        && null !== ($defaultValue = $field->getDefaultValue())) {
            $fieldSql .= ' DEFAULT ' . $this->_adapter->prepareValue($defaultValue, $field);
        }

        if ($field instanceof opal\schema\IAutoIncrementableField
        && $field->shouldAutoIncrement()) {
            $fieldSql .= ' AUTO_INCREMENT';
        }

        if ($field instanceof opal\schema\IAutoTimestampField
        && $field->shouldTimestampOnUpdate()) {
            $fieldSql .= ' ON UPDATE CURRENT_TIMESTAMP';
        }


        if (null !== ($comment = $field->getComment())) {
            $fieldSql .= ' COMMENT ' . $this->_adapter->prepareValue($comment);
        }

        return $fieldSql;
    }


    // Indexes
    protected function _generateInlineIndexDefinition($tableName, opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex = null)
    {
        if (null !== ($type = $index->getIndexType())) {
            switch ($type = strtoupper($type)) {
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

        if ($index === $primaryIndex) {
            $indexSql = 'PRIMARY KEY';
        } else {
            $indexSql = '';

            if ($index->isUnique()) {
                $indexSql .= 'UNIQUE ';
            } elseif ($type == 'FULLTEXT' || $type == 'SPACIAL') {
                $indexSql .= $type . ' ';
                $type = null;
            }

            $indexSql .= 'INDEX ' . $this->_adapter->quoteIdentifier($index->getName());
        }

        if ($type !== null
        && $type !== 'FULLTEXT'
        && $type !== 'SPACIAL') {
            $indexSql .= ' USING ' . $type;
        }

        $indexFields = [];

        foreach ($index->getFieldReferences() as $reference) {
            $fieldDef = $this->_adapter->quoteIdentifier($reference->getField()->getName());

            if (null !== ($indexSize = $reference->getSize())) {
                $fieldDef .= ' (' . $indexSize . ')';
            }

            if ($reference->isDescending()) {
                $fieldDef .= ' DESC';
            } else {
                $fieldDef .= ' ASC';
            }

            $indexFields[] = $fieldDef;
        }

        $indexSql .= ' (' . implode(',', $indexFields) . ')';

        if (version_compare($serverVersion, '5.1.0', '>=')) {
            if (null !== ($blockSize = $index->getKeyBlockSize())) {
                $indexSql .= ' KEY_BLOCK_SIZE ' . (int)$blockSize;
            }

            if ($type === 'FULLTEXT'
            && null !== ($fulltextParser = $index->getFulltextParser())) {
                $indexSql .= ' WITH PARSER ' . $this->_adapter->quoteValue($fulltextParser);
            }

            if (null !== ($comment = $index->getComment())
            && version_compare($serverVersion, '5.5.0', '>=')) {
                $indexSql .= ' COMMENT ' . $this->_adapter->prepareValue($comment);
            }
        }


        return $indexSql;
    }

    protected function _generateStandaloneIndexDefinition($tableName, opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex = null)
    {
        return null;
    }


    // Foreign keys : see Base


    // Triggers
    protected function _generateTriggerDefinition($tableName, opal\rdbms\schema\ITrigger $trigger)
    {
        switch ($trigger->getTimingName()) {
            case 'BEFORE':
            case 'AFTER':
                break;

            default:
                throw Exceptional::InvalidArgument(
                    'Mysql does not support ' . $trigger->getTimingName() . ' trigger timing'
                );
        }

        return parent::_generateTriggerDefinition($tableName, $trigger);
    }


    // Table options
    protected function _defineTableOptions(opal\rdbms\schema\ISchema $schema)
    {
        $sql = [];

        foreach ($schema->getOptionChanges() as $key => $value) {
            switch ($key) {
                case 'engine':
                    if ($value !== null) {
                        $sql[] = 'ENGINE ' . $value;
                    }

                    break;

                case 'avgRowLength':
                    if ($value !== null) {
                        $sql[] = 'AVG_ROW_LENGTH ' . (int)$value;
                    }

                    break;

                case 'autoIncrementPosition':
                    if ($value !== null) {
                        $sql[] = 'AUTO_INCREMENT ' . (int)$value;
                    }

                    break;

                case 'checksum':
                    $sql[] = 'CHECKSUM ' . (int)((bool)$value);
                    break;

                case 'characterSet':
                    if ($value === null) {
                        $value = 'DEFAULT';
                    }

                    $sql[] = 'CHARACTER SET ' . $this->_adapter->prepareValue($value);

                    break;

                case 'collation':
                    if ($value !== null) {
                        $sql[] = 'COLLATION ' . $this->_adapter->prepareValue($value);
                    }

                    break;

                case 'comment':
                    $sql[] = 'COMMENT ' . $this->_adapter->prepareValue($value);
                    break;

                case 'federatedConnection':
                    if ($value !== null && $schema instanceof Schema && $schema->getEngine() == 'FEDERATED') {
                        $sql[] = 'CONNECTION ' . $this->_adapter->prepareValue($value);
                    }

                    break;

                case 'dataDirectory':
                    if ($value !== null) {
                        $sql[] = 'DATA DIRECTORY ' . $this->_adapter->prepareValue($value);
                    }

                    break;

                case 'indexDirectory':
                    if ($value !== null) {
                        $sql[] = 'INDEX DIRECTORY ' . $this->_adapter->prepareValue($value);
                    }

                    break;

                case 'delayKeyWrite':
                    $sql[] = 'DELAY_KEY_WRITE ' . (int)((bool)$value);
                    break;

                case 'keyBlockSize':
                    if (version_compare($this->_adapter->getServerVersion(), '5.1.0', '>=')) {
                        if ($value === null || $value < 0) {
                            $value = 0;
                        }

                        $sql[] = 'KEY_BLOCK_SIZE ' . (int)$value;
                    }

                    break;

                case 'maxRows':
                    if ($value !== null) {
                        $sql[] = 'MAX_ROWS ' . (int)$value;
                    }

                    break;

                case 'minRows':
                    if ($value !== null) {
                        $sql[] = 'MIN_ROWS ' . (int)$value;
                    }

                    break;

                case 'packKeys':
                    if ($value === null) {
                        $value = 'DEFAULT';
                    } else {
                        $value = (int)$value;
                    }

                    $sql[] = 'PACK_KEYS ' . $value;
                    break;

                case 'rowFormat':
                    if ($value === null) {
                        $value = 'DEFAULT';
                    }

                    $sql[] = 'ROW_FORMAT ' . $value;
                    break;

                case 'insertMethod':
                    if ($value !== null) {
                        $sql[] = 'INSERT_METHOD ' . $value;
                    }

                    break;

                case 'mergeTables':
                    if (!empty($value) && is_array($value)) {
                        foreach ($value as &$table) {
                            $table = $this->_adapter->quoteIdentifier($table);
                        }

                        $sql[] = 'UNION (' . implode(', ', $value) . ')';
                    }

                    break;
            }
        }

        return $sql;
    }



    ## Alter ##
    public function alter($currentName, opal\rdbms\schema\ISchema $schema)
    {
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
        $tempSwapKeys = [];
        $removeKeys = $schema->getForeignKeysToRemove();
        $updateKeys = $schema->getForeignKeysToUpdate();
        $addKeys = $schema->getForeignKeysToAdd();

        foreach ($updateFields as $field) {
            foreach ($keys as $name => $key) {
                if ($key->hasField($field)) {
                    unset($updateKeys[$name]);

                    if (isset($renameKeys[$name])) {
                        $name = $renameKeys[$name];
                    }

                    $tempSwapKeys[$name] = $key;
                }
            }
        }

        $triggers = $schema->getTriggers();

        foreach ($triggers as $trigger) {
            if ($trigger->hasFieldReference($removeFields)) {
                $schema->removeTrigger($trigger->getName());
            }
        }

        $removeTriggers = $schema->getTriggersToRemove();
        $updateTriggers = $schema->getTriggersToUpdate();
        $addTriggers = $schema->getTriggersToAdd();

        $sql = [];
        $mainSql = 'ALTER TABLE ' . $this->_adapter->quoteIdentifier($currentName);


        // Remove triggers
        foreach ($removeTriggers as $name => $trigger) {
            $sql[] = 'DROP TRIGGER ' . $this->_adapter->quoteIdentifier($name);
        }

        foreach ($updateTriggers as $name => $trigger) {
            $sql[] = 'DROP TRIGGER ' . $this->_adapter->quoteIdentifier($name);
        }


        // Remove keys (to avoid conflicts)
        if (!empty($tempSwapKeys) || !empty($removeKeys)) {
            $swapSql = $mainSql;
            $definitions = [];

            foreach ($tempSwapKeys as $origName => $key) {
                $definitions[] = 'DROP FOREIGN KEY ' . $this->_adapter->quoteIdentifier($origName);
            }

            foreach ($removeKeys as $name => $key) {
                $definitions[] = 'DROP FOREIGN KEY ' . $this->_adapter->quoteIdentifier($name);
            }

            $swapSql .= "\n" . '    ' . implode(',' . "\n" . '    ', $definitions);
            $sql[] = $swapSql;
        }


        // Table options
        $definitions = $this->_defineTableOptions($schema);


        // Remove indexes
        foreach ($removeIndexes as $name => $index) {
            if ($index === $primaryIndex) {
                $definitions[] = 'DROP PRIMARY KEY';
            } else {
                $definitions[] = 'DROP INDEX ' . $this->_adapter->quoteIdentifier($name);
            }
        }

        foreach ($updateIndexes as $name => $index) {
            if ($index === $primaryIndex) {
                $definitions[] = 'DROP PRIMARY KEY';
            } else {
                $definitions[] = 'DROP INDEX ' . $this->_adapter->quoteIdentifier($name);
            }
        }


        // Remove fields
        foreach ($removeFields as $name => $field) {
            $definitions[] = 'DROP COLUMN ' . $this->_adapter->quoteIdentifier($field->getName());
        }

        // Update fields
        foreach ($updateFields as $name => $field) {
            $definitions[] = 'CHANGE COLUMN ' . $this->_adapter->quoteIdentifier($name) . ' ' . $this->_generateFieldDefinition($field);
        }

        // Add fields
        $lastField = null;

        foreach ($fields as $name => $field) {
            if (isset($addFields[$name])) {
                $fieldSql = 'ADD COLUMN ' . $this->_generateFieldDefinition($field);

                if ($lastField === null) {
                    $fieldSql .= ' FIRST';
                } else {
                    $fieldSql .= ' AFTER ' . $this->_adapter->quoteIdentifier($lastField->getName());
                }

                $definitions[] = $fieldSql;
            }

            $lastField = $field;
        }


        // Add indexes
        foreach ($updateIndexes as $name => $index) {
            $definitions[] = 'ADD ' . $this->_generateInlineIndexDefinition($newName, $index, $primaryIndex);
        }

        foreach ($addIndexes as $name => $index) {
            $definitions[] = 'ADD ' . $this->_generateInlineIndexDefinition($newName, $index, $primaryIndex);
        }


        // Add keys
        foreach ($tempSwapKeys as $key) {
            $definitions[] = 'ADD ' . $this->_generateInlineForeignKeyDefinition($key);
        }

        foreach ($addKeys as $key) {
            $definitions[] = 'ADD ' . $this->_generateInlineForeignKeyDefinition($key);
        }


        $mainSql .= "\n" . '    ' . implode(',' . "\n" . '    ', $definitions);


        $sql[] = $mainSql;


        // Add triggers
        foreach ($updateTriggers as $trigger) {
            $sql[] = $this->_generateTriggerDefinition($newName, $trigger);
        }

        foreach ($addTriggers as $trigger) {
            $sql[] = $this->_generateTriggerDefinition($newName, $trigger);
        }

        foreach ($sql as $query) {
            $this->_adapter->prepare($query)->executeRaw();
        }


        $schema->acceptChanges();
        return $this;
    }



    ## Character sets
    public function setCharacterSet($name, $charset, $collation = null, $convert = false)
    {
        $sql = 'ALTER TABLE `' . $name . '` ' . ($convert ? 'CONVERT TO ' : '') . 'CHARACTER SET :charset';

        if ($collation !== null) {
            $sql .= ' COLLATE :collation';
        }

        $stmt = $this->_adapter->prepare($sql);
        $stmt->bind('charset', $charset);

        if ($collation !== null) {
            $stmt->bind('collation', $collation);
        }

        $stmt->executeWrite();
        return $this;
    }

    public function getCharacterSet($name)
    {
        $stmt = $this->_adapter->prepare('SELECT CCSA.character_set_name FROM information_schema.`TABLES` T, information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA WHERE CCSA.collation_name = T.table_collation AND T.table_schema = :database AND T.table_name = :table');
        $stmt->bind('database', $this->_adapter->getDsn()->getDatabase());
        $stmt->bind('table', $name);
        $res = $stmt->executeRead();

        foreach ($res as $row) {
            return $row['character_set_name'];
        }

        return 'utf8';
    }

    public function setCollation($name, $collation, $convert = false)
    {
        $stmt = $this->_adapter->prepare('ALTER TABLE `' . $name . '` ' . ($convert ? 'CONVERT TO ' : '') . 'CHARACTER SET :charset COLLATE :collation');
        $stmt->bind('charset', explode('_', $collation)[0]);
        $stmt->bind('collation', $collation);
        $stmt->executeWrite();
        return $this;
    }

    public function getCollation($name)
    {
        $stmt = $this->_adapter->prepare('SELECT CCSA.collation_name FROM information_schema.`TABLES` T, information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA WHERE CCSA.collation_name = T.table_collation AND T.table_schema = :database AND T.table_name = :table');
        $stmt->bind('database', $this->_adapter->getDsn()->getDatabase());
        $stmt->bind('table', $name);
        $res = $stmt->executeRead();

        foreach ($res as $row) {
            return $row['collation_name'];
        }

        return 'utf8mb4_unicode_ci';
    }
}
