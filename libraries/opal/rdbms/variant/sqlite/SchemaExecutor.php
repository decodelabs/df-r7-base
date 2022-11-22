<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\variant\sqlite;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;

use df\flex;
use df\opal;

class SchemaExecutor extends opal\rdbms\SchemaExecutor
{
    ## Stats ##
    public function getTableStats($name)
    {
        Glitch::incomplete($name);
    }

    ## Introspect ##
    public function introspect($tableName)
    {
        $stmt = $this->_adapter->prepare($sql = 'SELECT * FROM sqlite_master WHERE tbl_name = :a');
        $stmt->bind('a', $tableName);

        $res = $stmt->executeRead();

        if ($res->isEmpty()) {
            throw Exceptional::{'df/opal/rdbms/TableNotFound,NotFound'}(
                'Table ' . $tableName . ' could not be found',
                [
                    'code' => 1,
                    'data' => [
                        'sql' => $stmt->getSql()
                    ]
                ]
            );
        }

        $triggers = [];
        $indexes = [];
        $tableData = null;

        /** @phpstan-ignore-next-line */
        while (!$res->isEmpty()) {
            $row = $res->extract();

            switch ($row['type']) {
                case 'table':
                    $tableData = $row;
                    break;

                case 'index':
                    if (!empty($row['sql'])) {
                        $indexes[$row['name']] = $row;
                    }

                    break;

                case 'trigger':
                    $triggers[$row['name']] = $row;
                    break;
            }
        }

        /** @phpstan-ignore-next-line */
        if (empty($tableData) || empty($tableData['sql'])) {
            throw Exceptional::{'df/opal/rdbms/TableNotFound,NotFound'}(
                'Table ' . $tableName . ' could not be found',
                [
                    'code' => 1,
                    'data' => [
                        'sql' => $sql
                    ]
                ]
            );
        }

        $schema = new Schema($this->_adapter, $tableName);
        $schema->isAudited(false);

        $parts = explode('(', substr($tableData['sql'], 0, -1), 2);
        $createSql = trim((string)array_shift($parts));
        $defSql = trim((string)preg_replace("/\s+/", ' ', (string)array_shift($parts)));

        if (preg_match('/TEMPORARY/i', $createSql)) {
            $schema->isTemporary(true);
        }

        if (false === ($defs = preg_split('/,[^0-9]/', $defSql))) {
            throw Exceptional::Runtime(
                'Unable to parse table def',
                null,
                $defSql
            );
        }

        foreach ($defs as $def) {
            if (preg_match('/^[`"]?([^"`]+)[`"]? ([a-zA-Z]+)( )?(\((.*)\))?/i', $def, $matches)) {
                // Field
                $args = isset($matches[5]) ?
                    flex\Delimited::parse($matches[5], ',', '\'')
                    : [];

                $field = $schema->addField($matches[1], $matches[2], ...$args);

                if (preg_match('/ NOT NULL( ON CONFLICT (ROLLBACK|ABORT|FAIL|IGNORE|REPLACE))?/i', $def, $matches)) {
                    $field->isNullable(false);

                    if (!empty($matches[2])) {
                        $field->setNullConflictClause($matches[2]);
                    }
                } else {
                    $field->isNullable(true);
                }

                if (preg_match('/ UNSIGNED/i', $def)) {
                    $field->isUnsigned(true);
                }

                if (preg_match('/ DEFAULT ([\'](.*)[\']|([\d]+))/i', $def, $matches)) {
                    if (isset($matches[3])) {
                        $field->setDefaultValue($matches[3]);
                    } else {
                        $field->setDefaultValue($matches[2]);
                    }
                }


                if (preg_match('/ (PRIMARY KEY|UNIQUE|REFERENCES) /i', $def, $matches)) {
                    $type = strtoupper($matches[2]);

                    switch ($type) {
                        // Column indexes
                        case 'PRIMARY KEY':
                        case 'UNIQUE':
                            $index = $schema->addUniqueIndex($field->getName(), $field);

                            if ($type === 'PRIMARY KEY') {
                                $schema->setPrimaryIndex($index);
                            }

                            if (preg_match('/ON CONFLICT (ROLLBACK|ABORT|FAIL|IGNORE|REPLACE)/i', $def, $matches)) {
                                $index->setConflictClause($matches[1]);
                            }

                            break;

                            // Foreign keys
                        case 'FOREIGN KEY':
                            if (!preg_match('/REFERENCES ["`]?([^"`]+)["`]?( \((.*)\))?( ON DELETE (SET NULL|SET DEFAULT|CASCADE|RESTRICT|NO ACTION))?( ON UPDATE (SET NULL|SET DEFAULT|CASCADE|RESTRICT|NO ACTION))?( (NOT )? DEFERRABLE( INITIALLY (DEFERRED|IMMEDIATE)))?/i', $def, $matches)) {
                                throw Exceptional::{'df/opal/rdbms/ForeignKeyConflict'}(
                                    'Unmatched foreign key: ' . $def
                                );
                            }

                            $key = $schema->addForeignKey($field->getName(), $matches[1]);
                            $references = flex\Delimited::parse($matches[3], ',', '"`');

                            $key->addReference($field, array_shift($references));

                            if (isset($matches[4])) {
                                $key->setDeleteAction($matches[5]);
                            }

                            if (isset($matches[6])) {
                                $key->setUpdateAction($matches[7]);
                            }

                            break;
                    }
                }
            } elseif (preg_match('/^CONSTRAINT [`"]?([^"`]+)["`]? (PRIMARY KEY|FOREIGN KEY|UNIQUE)/i', $def, $matches)) {
                $type = strtoupper($matches[2]);

                switch ($type) {
                    // Inline indexes
                    case 'PRIMARY KEY':
                    case 'UNIQUE':
                        $index = $schema->addUniqueIndex($matches[1], []);
                        preg_match('/' . $type . ' \((.*)\)/i', $def, $matches);

                        foreach (flex\Delimited::parse($matches[1], ',', null) as $part) {
                            $temp = explode(' ', trim($part), 2);
                            $fieldName = trim((string)array_shift($temp), '`\' ');
                            $isDescending = strtoupper(trim((string)array_shift($temp))) == 'DESC';

                            if (!$field = $schema->getField($fieldName)) {
                                throw Exceptional::{'df/opal/rdbms/IndexNotFound,NotFound'}(
                                    'Index field ' . $fieldName . ' could not be found'
                                );
                            }

                            $index->addField($field, null, $isDescending);
                        }

                        if ($type === 'PRIMARY KEY') {
                            $schema->setPrimaryIndex($index);
                        }

                        if (preg_match('/ON CONFLICT (ROLLBACK|ABORT|FAIL|IGNORE|REPLACE)/i', $def, $matches)) {
                            $index->setConflictClause($matches[1]);
                        }

                        break;

                        // Foreign keys
                    case 'FOREIGN KEY':
                        $keyName = $matches[1];

                        if (!preg_match('/FOREIGN KEY \((.*)\) REFERENCES ["`]?([^"`]+)["`]?( \((.*)\))?( ON DELETE (SET NULL|SET DEFAULT|CASCADE|RESTRICT|NO ACTION))?( ON UPDATE (SET NULL|SET DEFAULT|CASCADE|RESTRICT|NO ACTION))?( (NOT )? DEFERRABLE( INITIALLY (DEFERRED|IMMEDIATE)))?$/i', $def, $matches)) {
                            throw Exceptional::{'df/opal/rdbms/ForeignKeyConflict'}(
                                'Unmatched foreign key: ' . $def
                            );
                        }

                        $key = $schema->addForeignKey($keyName, $matches[2]);
                        $fields = flex\Delimited::parse($matches[1], ',', '"`');

                        $references = flex\Delimited::parse($matches[4], ',', '"`');

                        foreach ($fields as $fieldName) {
                            if (!$field = $schema->getField($fieldName)) {
                                throw Exceptional::{'df/opal/rdbms/ForeignKeyConflict'}(
                                    'Foreign key field ' . $fieldName . ' could not be found'
                                );
                            }

                            $key->addReference($field, array_shift($references));
                        }

                        if (isset($matches[5])) {
                            $key->setDeleteAction($matches[6]);
                        }

                        if (isset($matches[7])) {
                            $key->setUpdateAction($matches[8]);
                        }

                        break;
                }
            }
        }


        // Standalone indexes
        foreach ($indexes as $indexName => $indexData) {
            $index = $schema->addIndex($indexName, []);

            if (!preg_match('/INDEX ["`]?' . $indexData['name'] . '["`]? ON ["`]?' . $indexData['tbl_name'] . '["`]? \((.*)\)/i', $indexData['sql'], $matches)) {
                throw Exceptional::UnexpectedValue(
                    'Unmatched index: ' . $indexData['sql']
                );
            }

            foreach (flex\Delimited::parse($matches[1], ',', null) as $part) {
                $temp = explode(' ', trim($part), 2);
                $fieldName = trim((string)array_shift($temp), '`" ');
                $isDescending = strtoupper(trim((string)array_shift($temp))) == 'DESC';

                if (!$field = $schema->getField($fieldName)) {
                    throw Exceptional::{'df/opal/rdbms/IndexNotFound,NotFound'}(
                        'Index field ' . $fieldName . ' could not be found'
                    );
                }

                $index->addField($field, null, $isDescending);
            }
        }


        // Triggers
        foreach ($triggers as $triggerName => $triggerData) {
            if (!preg_match('/^CREATE (TEMP|TEMPORARY )?TRIGGER (IF NOT EXISTS )?["`]?' . $triggerName . '["`]?( (BEFORE|AFTER|INSTEAD OF))? (DELETE|INSERT|UPDATE)( OF (.*))? ON ["`]?' . $tableName . '["`]?( FOR EACH ROW)?( WHEN (.*))? BEGIN (.*) END$/i', $triggerData['sql'], $matches)) {
                throw Exceptional::UnexpectedValue(
                    'Unmatched trigger',
                    0,
                    $triggerData['sql']
                );
            }

            $trigger = $schema->addTrigger(
                $triggerName,
                $matches[5],
                strtoupper($matches[4]),
                explode(';', $matches[11])
            )
                ->isTemporary(!empty($matches[1]));

            if (!empty($matches[7])) {
                $cols = flex\Delimited::parse($matches[7], ',', '`"');
                array_map('trim', $cols);

                $trigger->setUpdateFields($cols);
            }

            if (!empty($matches[10])) {
                $trigger->setWhenExpression($matches[10]);
            }
        }


        // TODO: add stored procedures

        return $schema;
    }



    // Table
    protected function _generateTableOptions(opal\rdbms\schema\ISchema $schema)
    {
        return '';
    }



    // Fields
    protected function _generateFieldDefinition(opal\rdbms\schema\IField $field)
    {
        $fieldSql = $this->_adapter->quoteIdentifier($field->getName()) . ' ';

        if ($field instanceof opal\rdbms\schema\field\Binary
        || $field instanceof opal\rdbms\schema\field\Bit
        || $field instanceof opal\rdbms\schema\field\Char
        || $field instanceof opal\rdbms\schema\field\DateTime
        //|| $field instanceof opal\rdbms\schema\field\Enum
        || $field instanceof opal\rdbms\schema\field\Set
        || $field instanceof opal\rdbms\schema\field\Text) {
            $fieldSql .= 'TEXT';
        } elseif ($field instanceof opal\rdbms\schema\field\Blob) {
            $fieldSql .= 'BLOB';
        } elseif ($field instanceof opal\rdbms\schema\field\FloatingPoint) {
            $fieldSql .= 'REAL';
        } elseif ($field instanceof opal\rdbms\schema\field\Integer) {
            $fieldSql .= 'INTEGER';
        } else {
            $fieldSql .= $field->getType();
        }


        if ($field instanceof opal\schema\IOptionProviderField) {
            // do nothing :(
        } else {
            $options = [];

            if ($field instanceof opal\schema\ILengthRestrictedField
            && null !== ($length = $field->getLength())) {
                $options[] = $length;
            }

            if ($field instanceof opal\schema\IFloatingPointNumericField
            && null !== ($scale = $field->getScale())) {
                $options[] = $scale;
            }

            if (!empty($options)) {
                $fieldSql .= '(' . implode(',', $options) . ')';
            }
        }

        if (!$field->isNullable()) {
            $fieldSql .= ' NOT NULL';

            if (null !== ($conflictClause = $field->getNullConflictClauseName())) {
                $fieldSql .= ' ON CONFLICT ' . $conflictClause;
            }
        }

        if ($field instanceof opal\schema\IAutoTimestampField
        && ($field->shouldTimestampAsDefault() || !$field->isNullable())) {
            $fieldSql .= ' DEFAULT (DATETIME(\'now\'))';
        } elseif (null !== ($defaultValue = $field->getDefaultValue())) {
            $fieldSql .= ' DEFAULT ' . $this->_adapter->prepareValue($defaultValue, $field);
        } elseif (!$field->isNullable()) {
            $fieldSql .= ' DEFAULT ' . $this->_adapter->prepareValue('', $field);
        }

        if (null !== ($collation = $field->getCollation())) {
            $fieldSql .= ' COLLATE ' . $this->_adapter->quoteValue($collation);
        }

        return $fieldSql;
    }


    ## Exists ##
    public function exists($name)
    {
        $stmt = $this->_adapter->prepare('SELECT name FROM sqlite_master WHERE type = :a AND name = :b');
        $stmt->bind('a', 'table');
        $stmt->bind('b', $name);
        $res = $stmt->executeRaw();

        return (bool)$res->fetch();
    }



    ## Create ##

    // Indexes
    protected function _generateInlineIndexDefinition($tableName, opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex = null)
    {
        if (!$index->isUnique()) {
            return null;
        }

        $indexSql = 'CONSTRAINT ' . $this->_adapter->quoteIdentifier($tableName . '_' . $index->getName());

        if ($index === $primaryIndex) {
            $indexSql .= ' PRIMARY KEY';
        } else {
            $indexSql .= ' UNIQUE';
        }

        $indexFields = [];

        foreach ($index->getFieldReferences() as $reference) {
            $fieldDef = $this->_adapter->quoteIdentifier($reference->getField()->getName());

            if ($reference->isDescending()) {
                $fieldDef .= ' DESC';
            } else {
                $fieldDef .= ' ASC';
            }

            $indexFields[] = $fieldDef;
        }

        $indexSql .= ' (' . implode(',', $indexFields) . ')';

        if (null !== ($conflictClause = $index->getConflictClauseName())) {
            $indexSql .= ' ON CONFLICT ' . $conflictClause;
        }

        return $indexSql;
    }

    protected function _generateStandaloneIndexDefinition($tableName, opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex = null)
    {
        if ($index->isUnique()) {
            return null;
        }

        $indexSql = 'CREATE INDEX ' . $this->_adapter->quoteIdentifier($tableName . '_' . $index->getName());
        $indexSql .= ' ON ' . $this->_adapter->quoteIdentifier($tableName);

        $indexFields = [];

        foreach ($index->getFieldReferences() as $reference) {
            $fieldDef = $this->_adapter->quoteIdentifier($reference->getField()->getName());

            if ($reference->isDescending()) {
                $fieldDef .= ' DESC';
            } else {
                $fieldDef .= ' ASC';
            }

            $indexFields[] = $fieldDef;
        }

        $indexSql .= ' (' . implode(',', $indexFields) . ')';

        return $indexSql;
    }


    // Foreign keys
    protected function _generateInlineForeignKeyDefinition(opal\rdbms\schema\IForeignKey $key)
    {
        $keySql = parent::_generateInlineForeignKeyDefinition($key);

        // TODO: add MATCH clause
        // TODO: add DEFERRABLE clause

        return $keySql;
    }

    protected function _normalizeForeignKeyAction($action)
    {
        switch ($action = strtoupper($action)) {
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


    // Triggers
    protected function _generateTriggerDefinition($tableName, opal\rdbms\schema\ITrigger $trigger)
    {
        $triggerSql = 'CREATE';

        if ($trigger instanceof Trigger && $trigger->isTemporary()) {
            $triggerSql .= ' TEMPORARY';
        }

        $triggerSql .= ' TRIGGER ' . $this->_adapter->quoteIdentifier($trigger->getName());
        $triggerSql .= ' ' . $trigger->getTimingName();
        $triggerSql .= ' ' . $trigger->getEventName();

        if ($trigger instanceof Trigger && $trigger->getEvent() == opal\schema\ITriggerEvent::UPDATE) {
            $updateFields = $trigger->getUpdateFields();

            if (!empty($updateFields)) {
                foreach ($updateFields as &$field) {
                    $field = $this->_adapter->quoteIdentifier($field);
                }

                $triggerSql .= ' OF ' . implode(', ', $updateFields);
            }
        }

        $triggerSql .= ' ON ' . $this->_adapter->quoteIdentifier($tableName);
        $triggerSql .= ' FOR EACH ROW';

        if ($trigger instanceof Trigger && null !== ($trigger->getWhenExpression())) {
            $triggerSql .= ' WHEN ' . $trigger->getWhenExpression();
        }

        $triggerSql .= ' BEGIN ' . implode('; ', $trigger->getStatements()) . '; END';

        return $triggerSql;
    }




    ## Alter ##
    public function alter($currentName, opal\rdbms\schema\ISchema $schema)
    {
        $this->_adapter->executeSql('PRAGMA foreign_keys=OFF');
        $this->_adapter->begin();

        try {
            $backupName = 'backup_' . $currentName;//.'_'.uniqid();

            $addFields = $schema->getFieldsToAdd();
            $renameFields = $schema->getFieldRenameMap();
            $fields = $schema->getFields();

            $sourceFields = [];
            $destinationFields = [];

            foreach ($fields as $fieldName => $field) {
                if (isset($addFields[$fieldName])) {
                    continue;
                }

                $destinationFields[] = $this->_adapter->quoteIdentifier($fieldName);

                if (isset($renameFields[$fieldName])) {
                    $fieldName = $renameFields[$fieldName];
                }

                $sourceFields[] = $this->_adapter->quoteIdentifier($fieldName);
            }


            // Remove triggers
            $triggers = [];

            foreach ($schema->getTriggers() as $triggerName => $trigger) {
                $triggers[$triggerName] = $trigger;
                $schema->removeTrigger($triggerName);
                $trigger->setName($triggerName);
            }


            // Create target table
            $schema->acceptChanges()->isAudited(false);
            $schema->setName($backupName);
            $this->create($schema);
            $schema->setName($currentName);


            // Copy data
            $sql =
                'INSERT INTO ' . $this->_adapter->quoteIdentifier($backupName) . ' ' .
                '(' . implode(',', $destinationFields) . ') ' .
                'SELECT ' . implode(',', $sourceFields) . ' ' .
                'FROM ' . $this->_adapter->quoteIdentifier($currentName);

            $this->_adapter->executeSql($sql);

            // Drop original
            $sql = 'DROP TABLE ' . $this->_adapter->quoteIdentifier($currentName);
            $this->_adapter->executeSql($sql);

            // Rename target
            $sql =
                'ALTER TABLE ' . $this->_adapter->quoteIdentifier($backupName) . ' ' .
                'RENAME TO ' . $this->_adapter->quoteIdentifier($currentName);

            $this->_adapter->executeSql($sql);


            // Reinstate triggers
            foreach ($triggers as $trigger) {
                $schema->populateTrigger($trigger);
                $sql = $this->_generateTriggerDefinition($currentName, $trigger);
                $this->_adapter->executeSql($sql);
            }

            $this->_adapter->commit();
        } catch (\Throwable $e) {
            $this->_adapter->rollback();
            $this->_adapter->executeSql('PRAGMA foreign_keys=ON');
            throw $e;
        }

        $this->_adapter->executeSql('PRAGMA foreign_keys=ON');
        $this->_adapter->executeSql('VACUUM');


        return $this;
    }
}
