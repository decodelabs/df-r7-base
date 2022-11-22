<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch;
use df\opal;

abstract class SchemaExecutor implements ISchemaExecutor
{
    protected $_adapter;

    public static function factory(opal\rdbms\IAdapter $adapter)
    {
        $type = $adapter->getServerType();
        $class = 'df\\opal\\rdbms\\variant\\' . $type . '\\SchemaExecutor';

        if (!class_exists($class)) {
            throw Exceptional::Runtime(
                'There is no schema executor available for ' . $type
            );
        }

        return new $class($adapter);
    }

    protected function __construct(opal\rdbms\IAdapter $adapter)
    {
        $this->_adapter = $adapter;
    }

    public function getAdapter()
    {
        return $this->_adapter;
    }


    ## Exists ##
    public function exists($name)
    {
        $stmt = $this->_adapter->prepare('SHOW TABLES LIKE :name');
        $stmt->bind('name', $name);
        $res = $stmt->executeRead();

        return !$res->isEmpty();
    }


    ## Create ##
    public function create(opal\rdbms\schema\ISchema $schema)
    {
        // Table definition
        $sql = 'CREATE';

        if ($schema->isTemporary()) {
            $sql .= ' TEMPORARY';
        }

        $schemaName = $schema->getName();

        $sql .= ' TABLE ' . $this->_adapter->quoteIdentifier($schemaName) . ' (' . "\n";
        $definitions = [];

        // Fields
        foreach ($schema->getFields() as $field) {
            if (null !== ($def = $this->_generateFieldDefinition($field))) {
                $definitions[] = $def;
            }
        }


        // Indexes
        $primaryIndex = $schema->getPrimaryIndex();

        foreach ($schema->getIndexes() as $index) {
            if ($index->isVoid()) {
                throw Exceptional::{'df/opal/schema/Runtime'}(
                    'Index ' . $index->getName() . ' is invalid'
                );
            }

            if (null !== ($def = $this->_generateInlineIndexDefinition($schemaName, $index, $primaryIndex))) {
                $definitions[] = $def;
            }
        }


        // Foreign keys
        foreach ($schema->getForeignKeys() as $key) {
            if ($key->isVoid()) {
                throw Exceptional::{'df/opal/rdbms/ForeignKeyConflict'}(
                    'Foreign key ' . $key->getName() . ' is invalid'
                );
            }

            if (null !== ($def = $this->_generateInlineForeignKeyDefinition($key))) {
                $definitions[] = $def;
            }
        }


        // Flatten definitions
        $sql .= '    ' . implode(',' . "\n" . '    ', $definitions) . "\n" . ')' . $this->_generateTableOptions($schema) . "\n";



        // Table options
        $tableOptions = $this->_defineTableOptions($schema);

        if (!empty($tableOptions)) {
            $sql .= implode(',' . "\n", $tableOptions);
        }

        $sql = [$sql];


        // Indexes
        foreach ($schema->getIndexes() as $index) {
            if (null !== ($def = $this->_generateStandaloneIndexDefinition($schemaName, $index, $primaryIndex))) {
                $sql[] = $def;
            }
        }


        // Triggers
        foreach ($schema->getTriggers() as $trigger) {
            if (null !== ($def = $this->_generateTriggerDefinition($schemaName, $trigger))) {
                $sql[] = $def;
            }
        }


        // TODO: stored procedures

        try {
            foreach ($sql as $query) {
                $this->_adapter->prepare($query)->executeRaw();
            }
        } catch (\Throwable $e) {
            $this->drop($schemaName);

            throw $e;
        }

        return $this;
    }


    // Tables
    abstract protected function _generateTableOptions(opal\rdbms\schema\ISchema $schema);

    // Fields
    abstract protected function _generateFieldDefinition(opal\rdbms\schema\IField $field);

    // Indexes
    abstract protected function _generateInlineIndexDefinition($tableName, opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex = null);
    abstract protected function _generateStandaloneIndexDefinition($tableName, opal\rdbms\schema\IIndex $index, opal\rdbms\schema\IIndex $primaryIndex = null);


    // Foreign keys
    protected function _generateInlineForeignKeyDefinition(opal\rdbms\schema\IForeignKey $key)
    {
        $keySql = 'CONSTRAINT ' . $this->_adapter->quoteIdentifier($key->getName()) . ' FOREIGN KEY';
        $fields = [];
        $references = [];

        foreach ($key->getReferences() as $reference) {
            $fields[] = $this->_adapter->quoteIdentifier($reference->getField()->getName());
            $references[] = $this->_adapter->quoteIdentifier($reference->getTargetFieldName());
        }

        $keySql .= ' (' . implode(',', $fields) . ')';
        $keySql .= ' REFERENCES ' . $this->_adapter->quoteIdentifier($key->getTargetSchema());
        $keySql .= ' (' . implode(',', $references) . ')';

        if (null !== ($action = $key->getDeleteAction())) {
            $action = $this->_normalizeForeignKeyAction($action);
            $keySql .= ' ON DELETE ' . $action;
        }

        if (null !== ($action = $key->getUpdateAction())) {
            $action = $this->_normalizeForeignKeyAction($action);
            $keySql .= ' ON UPDATE ' . $action;
        }

        return $keySql;
    }

    protected function _normalizeForeignKeyAction($action)
    {
        switch ($action = strtoupper($action)) {
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
    protected function _generateTriggerDefinition($tableName, opal\rdbms\schema\ITrigger $trigger)
    {
        $triggerSql = 'CREATE TRIGGER ' . $this->_adapter->quoteIdentifier($trigger->getName());
        $triggerSql .= $trigger->getTimingName();
        $triggerSql .= ' ' . $trigger->getEventName();
        $triggerSql .= ' ON ' . $this->_adapter->quoteIdentifier($tableName);
        $triggerSql .= ' FOR EACH ROW BEGIN ' . implode('; ', $trigger->getStatements()) . '; END';

        return $triggerSql;
    }

    // Table options
    protected function _defineTableOptions(opal\rdbms\schema\ISchema $schema)
    {
        return null;
    }


    ## Rename ##
    public function rename($oldName, $newName)
    {
        $sql =
            'ALTER TABLE ' . $this->_adapter->quoteIdentifier($oldName) . ' ' .
            'RENAME TO ' . $this->_adapter->quoteIdentifier($newName);

        $this->_adapter->prepare($sql)->executeRaw();
        return $this;
    }


    ## Drop ##
    public function drop($name)
    {
        $sql = 'DROP TABLE IF EXISTS ' . $this->_adapter->quoteIdentifier($name);
        $this->_adapter->prepare($sql)->executeRaw();
        return $this;
    }



    ## Character sets
    public function setCharacterSet($name, $set, $collation = null, $convert = false)
    {
        Glitch::incomplete($name);
    }

    public function getCharacterSet($name)
    {
        Glitch::incomplete($name);
    }

    public function setCollation($name, $collation, $convert = false)
    {
        Glitch::incomplete($name);
    }

    public function getCollation($name)
    {
        Glitch::incomplete($name);
    }
}
