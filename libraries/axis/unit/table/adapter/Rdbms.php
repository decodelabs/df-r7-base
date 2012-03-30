<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\adapter;

use df;
use df\core;
use df\axis;
use df\opal;

class Rdbms implements 
    axis\ISchemaProviderAdapter, 
    opal\query\IAdapter,
    core\IDumpable {
    
    protected $_rdbmsAdapter;
    protected $_querySourceAdapter;    
    protected $_unit;
    
    public function __construct(axis\IAdapterBasedStorageUnit $unit) {
        $this->_unit = $unit;
    }
    
    
// Query source
    public function getQuerySourceId() {
        return 'axis://Unit:"'.$this->_unit->getUnitId().'"';
    }
    
    public function getQuerySourceDisplayName() {
        return $this->_unit->getUnitId();
    }
    
    public function getQuerySourceAdapterHash() {
        return $this->_getRdbmsAdapter()->getDsnHash();
    }
    
    public function getQuerySourceAdapter() {
        if(!$this->_querySourceAdapter) {
            $this->_querySourceAdapter = $this->_getRdbmsAdapter()->getTable($this->_getTableName());
        }
        
        return $this->_querySourceAdapter;
    }
    
    protected function _getRdbmsAdapter() {
        if(!$this->_rdbmsAdapter) {
            $config = axis\ConnectionConfig::getInstance($this->_unit->getModel()->getApplication());
            $settings = $config->getSettingsFor($this->_unit);
            $this->_rdbmsAdapter = opal\rdbms\adapter\Base::factory($settings['dsn']);
        }
        
        return $this->_rdbmsAdapter;
    }
    
    public function getDelegateQueryAdapter() {
        return $this->getQuerySourceAdapter()->getDelegateQueryAdapter();
    }
    
    public function supportsQueryType($type) {
        return $this->getQuerySourceAdapter()->supportsQueryType($type);
    }
    
    public function supportsQueryFeature($feature) {
        return $this->getQuerySourceAdapter()->supportsQueryFeature($feature);
    }
    
    
    
// Query proxy
    public function executeSelectQuery(opal\query\ISelectQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null) {
        return $this->getQuerySourceAdapter()->executeSelectQuery($query, $keyField, $valField);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        return $this->getQuerySourceAdapter()->countSelectQuery($query);
    }
    
    public function executeFetchQuery(opal\query\IFetchQuery $query, opal\query\IField $keyField=null) {
        return $this->getQuerySourceAdapter()->executeFetchQuery($query, $keyField);
    }

    public function countFetchQuery(opal\query\IFetchQuery $query) {
        return $this->getQuerySourceAdapter()->countFetchQuery($query);
    }

    public function executeInsertQuery(opal\query\IInsertQuery $query) {
        return $this->getQuerySourceAdapter()->executeInsertQuery($query);
    }

    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        return $this->getQuerySourceAdapter()->executeBatchInsertQuery($query);
    }

    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        return $this->getQuerySourceAdapter()->executeReplaceQuery($query);
    }

    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        return $this->getQuerySourceAdapter()->executeBatchReplaceQuery($query);
    }

    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {
        return $this->getQuerySourceAdapter()->executeUpdateQuery($query);
    }

    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {
        return $this->getQuerySourceAdapter()->executeDeleteQuery($query);
    }
    
    
    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        return $this->getQuerySourceAdapter()->fetchRemoteJoinData($join, $rows);
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        return $this->getQuerySourceAdapter()->fetchAttachmentData($attachment, $rows);
    }

    
    

// Transactions
    public function beginQueryTransaction() {
        return $this->getQuerySourceAdapter()->beginQueryTransaction();
    }

    public function commitQueryTransaction() {
        return $this->getQuerySourceAdapter()->commitQueryTransaction();
    }

    public function rollbackQueryTransaction() {
        return $this->getQuerySourceAdapter()->rollbackQueryTransaction();
    }
    
    
// Record
    public function newRecord(array $values=null) {
        return $this->_unit->newRecord($values);
    }
    
    
    
    
// Create
    public function createStorageFromSchema(axis\schema\ISchema $axisSchema) {
        $adapter = $this->_getRdbmsAdapter();
        $dbSchema = $adapter->newSchema($this->_getTableName());
        $this->_applySchemaChanges($dbSchema, $axisSchema);
        
        try {
            return $adapter->createTable($dbSchema);
        } catch(opal\rdbms\TableConflictException $e) {
            // TODO: check db schema matches
            
            return $adapter->getTable($dbSchema->getName());
        }
    }
    
    public function destroyStorage() {
        $table = $this->getQuerySourceAdapter();
        $table->drop();
        
        return $this;
    }
    
    protected function _getTableName() {
        $model = $this->_unit->getModel();
        return $model->getApplication()->getUniquePrefix().'_'.$model->getModelName().'_'.$this->_unit->getCanonicalUnitName();
    }


// Schema
    protected function _applySchemaChanges(opal\rdbms\schema\ISchema $dbSchema, axis\schema\ISchema $axisSchema) {
        $axisPrimaryIndex = $axisSchema->getPrimaryIndex();
        $lastAxisPrimaryIndex = $axisSchema->getLastPrimaryIndex();
        $dbPrimaryIndex = $dbSchema->getPrimaryIndex();
        
        if(!$primaryIndexHasChanged = $axisSchema->hasPrimaryIndexChanged()) {
            $lastAxisPrimaryIndex = $axisPrimaryIndex;
        }
        
        
        // Remove indexes
        foreach($axisSchema->getIndexesToRemove() as $name => $axisIndex) {
            $dbName = $this->_getIndexName($axisIndex, $axisIndex === $lastAxisPrimaryIndex);
            $dbSchema->removeIndex($dbName);
        }
        
        // Remove fields
        foreach($axisSchema->getFieldsToRemove() as $name => $axisField) {
            if($axisField instanceof axis\schema\IMultiPrimitiveField) {
                foreach($axisField->getPrimitiveFieldNames() as $name) {
                    $dbSchema->removeField($name);
                }
            } else if($axisField instanceof axis\schema\INullPrimitiveField) {
                continue;
            } else {
                $dbSchema->removeField($name);
            }
        }
        
        // Update fields
        foreach($axisSchema->getFieldsToUpdate() as $name => $axisField) {
            $primitive = $axisField->toPrimitive($this->_unit, $axisSchema);
            
            if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                $childPrimitives = $primitive->getPrimitives();
                $lastChild = $firstChild = array_shift($childPrimitives);
                
                $dbSchema->replacePreparedField(
                    $this->_createField($dbSchema, $firstChild)
                );
                
                foreach($childPrimitives as $child) {
                    $dbSchema->addPreparedFieldAfter(
                        $lastChild->getName(), $this->_createField($dbSchema, $child)
                    );
                    
                    $lastChild = $child;
                }
            } else if($axisField instanceof axis\schema\INullPrimitiveField) {
                continue;
            } else {
                $dbSchema->replacePreparedField(
                    $this->_createField($dbSchema, $primitive)
                );
            }
        }
        
        // Add fields
        foreach($axisSchema->getFieldsToAdd() as $name => $axisField) {
            $primitive = $axisField->toPrimitive($this->_unit, $axisSchema);
            
            if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach($primitive->getPrimitives() as $name => $child) {
                    $dbSchema->addPreparedField(
                        $this->_createField($dbSchema, $child)
                    );
                }
            } else if($axisField instanceof axis\schema\INullPrimitiveField) {
                continue;
            } else {
                $dbSchema->addPreparedField(
                    $this->_createField($dbSchema, $primitive)
                );
            }
        }
        
        // Update indexes
        foreach($axisSchema->getIndexesToUpdate() as $name => $axisIndex) {
            if($primaryIndexHasChanged 
            && $axisIndex === $lastAxisPrimaryIndex
            && $dbPrimaryIndex) {
                $newName = $this->_getIndexName($axisIndex, false);
                $oldName = $dbPrimaryIndex->getName();
                
                if($newName != $oldName) {
                    $dbSchema->renameIndex($oldName, $newName);
                }
            }
            
            $isPrimary = $axisIndex === $axisPrimaryIndex;
            
            $dbSchema->replacePreparedIndex(
                $this->_createIndex($axisSchema, $dbSchema, $axisIndex, $isPrimary)
            );
            
            if($isPrimary) {
                $dbSchema->setPrimaryIndex($dbIndex);
            }
        }
        
        // Add indexes
        foreach($axisSchema->getIndexes() as $name => $axisIndex) {
            $isPrimary = $axisIndex === $axisPrimaryIndex;
            
            $dbSchema->addPreparedIndex(
                $dbIndex = $this->_createIndex($axisSchema, $dbSchema, $axisIndex, $isPrimary)
            );
            
            if($isPrimary) {
                if($dbPrimaryIndex) {
                    $newName = $this->_getIndexName($axisIndex, false);
                    $oldName = $dbPrimaryIndex->getName();
                    
                    if($newName != $oldName) {
                        $dbSchema->renameIndex($oldName, $newName);
                    }
                }
                
                $dbSchema->setPrimaryIndex($dbIndex);
            }
        }
    }


// Primitives
    protected function _createField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $type = $primitive->getType();
        $func = '_create'.$type.'Field';
        
        if(!method_exists($this, $func)) {
            throw new axis\schema\RuntimeException(
                'Primitive '.$type.' is currently not supported by RDBMS based tables'
            );
        }
        
        return $this->{$func}($schema, $primitive);
    }


// Binary
    protected function _createBinaryField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'binary', $primitive->getLength());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Bit
    protected function _createBitField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'bit', $primitive->getBitSize());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Blob
    protected function _createBlobField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        switch($primitive->getExponentSize()) {
            case 8: $type = 'tinyblob'; break;
            case 16: $type = 'blob'; break;
            case 24: $type = 'mediumblob'; break;
            case 32: $type = 'longblob'; break;
        }
            
        $field = $schema->createField($primitive->getName(), $type);
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Boolean
    protected function _createBooleanField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'bool');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Char
    protected function _createCharField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'char', $primitive->getLength())
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Currency
    protected function _createCurrencyField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'decimal', 19, 4);
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Dataobject
    protected function _createDataObjectField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'blob');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Date
    protected function _createDateField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'date');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Datetime
    protected function _createDateTimeField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'datetime');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Decimal
    protected function _createDecimalField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'decimal', $primitive->getPrecision(), $primitive->getScale());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Enum
    protected function _createEnumField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'enum', $primitive->getOptions())
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Float
    protected function _createFloatField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'float', $primitive->getPrecision(), $primitive->getScale());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Guid
    protected function _createGuidField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'binary', 16);
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Integer
    protected function _createIntegerField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        switch($primitive->getByteSize()) {
            case 1: $type = 'tinyint'; break;
            case 2: $type = 'smallint'; break;
            case 3: $type = 'mediumint'; break;
            case 4: $type = 'int'; break;
            case 8: $type = 'bigint'; break;
        }
        
        $field = $schema->createField($primitive->getName(), $type)
            ->isUnsigned($primitive->isUnsigned())
            ->shouldZerofill($primitive->shouldZerofill())
            ->shouldAutoIncrement($primitive->shouldAutoIncrement());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    


// Set
    protected function _createSetField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'set', $primitive->getOptions())
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Text
    protected function _createTextField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        switch($primitive->getExponentSize()) {
            case 8: $type = 'tinytext'; break;
            case 16: $type = 'text'; break;
            case 24: $type = 'mediumtext'; break;
            case 32: $type = 'longtext'; break;
        }
        
        $field = $schema->createField($primitive->getName(), $type)
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Time
    protected function _createTimeField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'time');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Timestamp
    protected function _createTimestampField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'timestamp')
            ->shouldTimestampAsDefault($primitive->shouldTimestampAsDefault())
            ->shouldTimestampOnUpdate($primitive->shouldTimestampOnUpdate());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        
        if($primitive->shouldTimestampAsDefault()) {
            $field->setDefaultValue(null);
        }
        
        return $field;
    }

// Varbinary
    protected function _createVarbinaryField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'varbinary', $primitive->getLength());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Varchar
    protected function _createVarcharField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'varchar', $primitive->getLength())
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Year
    protected function _createYearField(opal\rdbms\schema\ISchema $schema, opal\schema\IPrimitive $primitive) {
        $field = $schema->createField($primitive->getName(), 'smallint');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }



    
    
// Base options
    protected function _importBasePrimitiveOptions(opal\rdbms\schema\IField $field, opal\schema\IPrimitive $primitive) {
        $field->isNullable($primitive->isNullable())
            ->setDefaultValue($primitive->getDefaultValue())
            ->setComment($primitive->getComment());
    }


// Indexes
    protected function _createIndex(axis\schema\ISchema $axisSchema, opal\rdbms\schema\ISchema $dbSchema, opal\schema\IIndex $axisIndex, $isPrimary) {
        $dbIndex = $dbSchema->createIndex($this->_getIndexName($axisIndex, $isPrimary), array())
            ->isUnique($axisIndex->isUnique());
        
        foreach($axisIndex->getFieldReferences() as $ref) {
            $axisField = $ref->getField();
            $primitive = $axisField->toPrimitive($this->_unit, $axisSchema);
            
            if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach($primitive->getPrimitives() as $name => $child) {
                    $dbIndex->addField(
                        $schema->getField($child->getName()), 
                        $ref->getSize(), 
                        $ref->isDescending()
                    );
                }
            } else if($axisField instanceof axis\schema\INullPrimitiveField) {
                throw new axis\LogicException(
                    'You cannot put indexes on NullPrimitive fields'
                );
            } else {
                $dbIndex->addField(
                    $dbSchema->getField($primitive->getName()), 
                    $ref->getSize(), 
                    $ref->isDescending()
                );
            }
        }
        
        return $dbIndex;
    }
    
    protected function _getIndexName(opal\schema\IIndex $axisIndex, $isPrimary) {
        if($isPrimary && $this->_getRdbmsAdapter()->getServerType() == 'mysql') {
            return 'PRIMARY';
        } 
        
        return 'idx_'.$axisIndex->getName();
    }



// Schema storage
    public function fetchSchema() {
        $adapter = $this->_getRdbmsAdapter();
        $table = $adapter->getTable($this->_getSchemaTableName());
        
        try {
            $schema = $table->select('schema')
                ->where('unitId', '=', $this->_unit->getUnitId())
                ->toValue();
                
            if($schema === null) {
                return null;
            }
            
            if(!$schema = unserialize($schema)) {
                return null;
            }
            
            return $schema;
        } catch(opal\rdbms\TableNotFoundException $e) {
            $this->_createSchemaTable($this->_getSchemaTableSchema());
            return null;
        } catch(\Exception $e) {
            return null;
        }
    }

    public function storeSchema(axis\schema\ISchema $schema) {
        $adapter = $this->_getRdbmsAdapter();
        $table = $adapter->getTable($this->_getSchemaTableName());
        
        $current = $table->select('timestamp')
            ->where('unitId', '=', $this->_unit->getUnitId())
            ->toValue('timestamp');
            
        if($current === null) {
            $table->insert(array(
                    'unitId' => $this->_unit->getUnitId(),
                    'tableName' => $this->_getTableName(),
                    'version' => $schema->getVersion(),
                    'schema' => serialize($schema)
                ))
                ->execute();
        } else {
            $table->update(array(
                    'schema' => serialize($schema),
                    'version' => $schema->getVersion()
                ))
                ->where('unitId', '=', $this->_unit->getUnitId())
                ->execute();
        }
        
        return $this;
    }
    
    public function unstoreSchema() {
        $adapter = $this->_getRdbmsAdapter();
        
        try {
            $schemaTable = $adapter->getTable($this->_getSchemaTableName());
            $schemaTable->delete()
                ->where('unitId', '=', $this->_unit->getUnitId())
                ->execute();
        } catch(opal\rdbms\TableNotFoundException $e) {}
            
        return $this;
    }
    

    protected function _createSchemaTable(axis\schema\ISchema $schema) {
        $adapter = $this->_getRdbmsAdapter();
        $dbSchema = $adapter->newSchema($schema->getName());
        $this->_applySchemaChanges($dbSchema, $schema);
        
        return $adapter->createTable($dbSchema);
    }
    
    protected function _getSchemaTableName() {
        return $this->_unit->getModel()->getApplication()->getUniquePrefix().'_axis_schemas';
    }
    
    protected function _getSchemaTableSchema() {
        $schema = new axis\schema\Base($this->_unit, $this->_getSchemaTableName());
        
        $schema->addField('unitId', 'String', 64);
        $schema->addField('tableName', 'String', 128);
        $schema->addField('version', 'Integer', 1);
        $schema->addField('schema', 'BigBinary', 16);
        $schema->addField('timestamp', 'Timestamp');
        
        $schema->addPrimaryIndex('unitId');
        $schema->addIndex('timestamp');
        
        return $schema;
    }


    
// Query exceptions
    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        // Table not found
        if($e instanceof opal\rdbms\TableNotFoundException && $e->table == $this->_getTableName()) {
            $this->_unit->destroyStorage();
            $this->_unit->getUnitSchema();
            
            return true;
        }
        
        switch($query->getQueryType()) {
            // TODO: do something here :)
        }
        
        return false;
    }

    
    
// Dump
    public function getDumpProperties() {
        return array(
            'adapter' => get_class($this),
            'unit' => $this->_unit->getUnitId() 
        );
    }
}
