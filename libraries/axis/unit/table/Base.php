<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table;

use df;
use df\core;
use df\axis;
use df\opal;

abstract class Base extends axis\Unit implements 
    axis\ISchemaBasedStorageUnit, 
    core\policy\IParentEntity, 
    opal\query\IEntryPoint,
    opal\query\IIntegralAdapter,
    core\IDumpable {
    
    protected $_adapter;
    
    private $_recordClass;
    private $_schema;
    
    public function __construct(axis\IModel $model, $unitName=null) {
        parent::__construct($model);
        $this->_adapter = self::loadAdapter($this);
    }
    
    public function getUnitType() {
        return 'table';
    }
    
    public function getUnitAdapter() {
        return $this->_adapter;
    }
    
    public function getStorageBackendName() {
        $output = $this->_model->getModelName().'_'.$this->getCanonicalUnitName();

        if($this->_shouldPrefixNames()) {
            $output = $this->_model->getApplication()->getUniquePrefix().'_'.$output;
        }

        return $output;
    }
    
    public function getBridgeUnit($fieldName) {
        $schema = $this->getUnitSchema();
        $field = $schema->getField($fieldName);
        
        if(!$field instanceof axis\unit\table\schema\field\IBridgedRelationField) {
            throw new axis\LogicException(
                'Unit '.$this->getUnitId().' does not have a bridge field named '.$fieldName
            );
        }
        
        return $field->getBridgeUnit($this->getModel()->getApplication());
    }
    
// Schema
    public function getUnitSchema() {
        if($this->_schema === null) {
            $this->_schema = $this->_model->getSchemaDefinitionUnit()->fetchFor($this);
        }
        
        return $this->_schema;
    }
    
    public function getTransientUnitSchema() {
        if($this->_schema !== null) {
            return $this->_schema;
        }
        
        return $this->_model->getSchemaDefinitionUnit()->fetchFor($this, true);
    }
    
    public function buildInitialSchema() {
        return new axis\schema\Base($this, $this->getUnitName());
    }
    
    public function clearUnitSchemaCache() {
        $this->_schema = null;
        
        $cache = axis\schema\Cache::getInstance($this->_model->getApplication());
        $cache->remove($this->getUnitId());
        
        return $this;
    }
    
    public function updateUnitSchema(axis\schema\ISchema $schema) {
        $version = $schema->getVersion();
        
        if($version === 0) {
            $this->_onCreate($schema);
            $schema->iterateVersion();
        }
        
        while(true) {
            $version = $schema->getVersion();
            $func = '_onUpdateVersion'.$version;
            
            if(!method_exists($this, $func)) {
                break;
            }
            
            $this->{$func}($schema);
            $schema->iterateVersion();
        }
        
        $schema->sanitize($this);
        
        return $this;
    }
    
    abstract protected function _onCreate(axis\schema\ISchema $schema);
    
    public function validateUnitSchema(axis\schema\ISchema $schema) {
        $schema->validate($this);
        return $this;
    }
    
    public function createStorageFromSchema(axis\schema\ISchema $schema) {
        $this->_adapter->createStorageFromSchema($schema);
        return $this;
    }
    
    
    public function destroyStorage() {
        $this->_adapter->destroyStorage();
        $this->_model->getSchemaDefinitionUnit()->remove($this);
        
        return $this;
    }
    
    
    
// Query source
    public function getQuerySourceId() {
        return $this->_adapter->getQuerySourceId();
    }
    
    public function getQuerySourceAdapterHash() {
        return $this->_adapter->getQuerySourceAdapterHash();
    }
    
    public function getQuerySourceDisplayName() {
        return $this->_adapter->getQuerySourceDisplayName();
    }
    
    public function getDelegateQueryAdapter() {
        return $this->_adapter->getDelegateQueryAdapter();
    }
    
    public function supportsQueryType($type) {
        return $this->_adapter->supportsQueryType($type);
    }
    
    public function supportsQueryFeature($feature) {
        switch($feature) {
            case opal\query\IQueryFeatures::VALUE_PROCESSOR:
                return true;
                
            default:
                return $this->_adapter->supportsQueryFeature($feature);
        }
    }
    
    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        return $this->_adapter->handleQueryException($query, $e);
    }
    
    
    
    
// Query proxy
    public function executeSelectQuery(opal\query\ISelectQuery $query, opal\query\IField $keyField=null, opal\query\IField $valField=null) {
        return $this->_adapter->executeSelectQuery($query, $keyField, $valField);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        return $this->_adapter->countSelectQuery($query);
    }
    
    public function executeFetchQuery(opal\query\IFetchQuery $query, opal\query\IField $keyField=null) {
        return $this->_adapter->executeFetchQuery($query, $keyField);
    }

    public function countFetchQuery(opal\query\IFetchQuery $query) {
        return $this->_adapter->countFetchQuery($query);
    }

    public function executeInsertQuery(opal\query\IInsertQuery $query) {
        return $this->_adapter->executeInsertQuery($query);
    }

    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        return $this->_adapter->executeBatchInsertQuery($query);
    }

    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        return $this->_adapter->executeReplaceQuery($query);
    }

    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        return $this->_adapter->executeBatchReplaceQuery($query);
    }

    public function executeUpdateQuery(opal\query\IUpdateQuery $query) {
        return $this->_adapter->executeUpdateQuery($query);
    }

    public function executeDeleteQuery(opal\query\IDeleteQuery $query) {
        return $this->_adapter->executeDeleteQuery($query);
    }
    
    
    public function fetchRemoteJoinData(opal\query\IJoinQuery $join, array $rows) {
        return $this->_adapter->fetchRemoteJoinData($join, $rows);
    }

    public function fetchAttachmentData(opal\query\IAttachQuery $attachment, array $rows) {
        return $this->_adapter->fetchAttachmentData($attachment, $rows);
    }

    

// Query helpers
    public function dereferenceQuerySourceWildcard(opal\query\ISource $source) {
        $output = array();
        
        foreach($this->getUnitSchema()->getFields() as $name => $field) {
            $output[] = $this->extrapolateQuerySourceField($source, $name, null, $field);
        }
        
        return $output;
    }
    
    public function extrapolateQuerySourceField(opal\query\ISource $source, $name, $alias=null, opal\schema\IField $field=null) {
        if($field === null) {
            if(!$field = $this->getUnitSchema()->getField($name)) {
                return new opal\query\field\Intrinsic($source, $name, $alias);
            }
        }
        
        if($field instanceof axis\schema\IMultiPrimitiveField) {
            $privateFields = array();
            
            foreach($field->getPrimitiveFieldNames() as $fieldName) {
                $privateFields[] = new opal\query\field\Intrinsic($source, $fieldName, $alias);
            }
            
            $output = new opal\query\field\Virtual($source, $name, $alias, $privateFields);
        } else if($field instanceof axis\schema\INullPrimitiveField) {
            $output = new opal\query\field\Virtual($source, $name, $alias);
        } else {
            $output = new opal\query\field\Intrinsic($source, $name, $alias);
        }
        
        return $output;
    }




// Clause helpers
    public function prepareQueryClauseValue(opal\query\IField $field, $value) {
        $schema = $this->getUnitSchema();
        
        if(!$axisField = $schema->getField($field->getName())) {
            return $value;
        }
        
        return $axisField->deflateValue($axisField->sanitizeValue($value, false));
    }
    
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        if((!$axisField = $this->getUnitSchema()->getField($field->getName()))
         || !$axisField instanceof axis\schema\IQueryClauseRewriterField) {
            return null;
        }
        
        return $axisField->rewriteVirtualQueryClause($parent, $field, $operator, $value, $isOr);
    }
    
    
    
    
// Value processors
    public function getQueryResultValueProcessors(array $fields=null) {
        $schema = $this->getUnitSchema();
        
        if($fields === null) {
            return $schema->getFields();
        }
        
        $output = array();
        
        foreach($fields as $fieldName) {
            if($field = $schema->getField($fieldName)) {
                $output[$fieldName] = $field;
            }
        }
        
        return $output;
    }
    
    
    public function deflateInsertValues(array $row) {
        $schema = $this->getUnitSchema();
        $values = array();
        
        foreach($schema->getFields() as $name => $field) {
            if($field instanceof axis\schema\INullPrimitiveField) {
                continue;
            }
            
            if(!isset($row[$name])) {
                $value = $field->generateInsertValue($row);
            } else {
                $value = $field->sanitizeValue($row[$name], false);
            }
            
            $value = $field->deflateValue($value);
        
            if(is_array($value)) {
                foreach($value as $key => $val) {
                    $values[$key] = $val;
                }
            } else {
                $values[$name] = $value;
            }
        }
        
        return $values;
    }
    
    public function normalizeInsertId($originalId, array $row) {
        if(null === ($fields = $this->getRecordPrimaryFieldNames())) {
            return null;
        }
        
        $schema = $this->getUnitSchema();
        $values = array();
        
        foreach($fields as $name) {
            $field = $schema->getField($name);
            
            if($originalId 
            && (($field instanceof opal\schema\IAutoIncrementableField && $field->shouldAutoIncrement())
              || $field instanceof axis\schema\IAutoGeneratorField)) {
                $values[$name] = $originalId;
            } else {
                $values[$name] = $field->inflateValueFromRow($name, $row, false);
            }
        }
        
        return new opal\query\record\PrimaryManifest($fields, $values);
    }
    
    public function deflateBatchInsertValues(array $rows, array &$queryFields) {
        $schema = $this->getUnitSchema();
        $fields = $schema->getFields();
        $queryFields = array_keys($fields);
        $values = array();
        
        foreach($rows as $row) {
            $rowValues = array();
            
            foreach($fields as $name => $field) {
                if($field instanceof axis\schema\INullPrimitiveField) {
                    continue;
                }
                
                if(!isset($row[$name])) {
                    $value = $field->generateInsertValue($row);
                } else {
                    $value = $field->sanitizeValue($row[$name], false);
                }
                
                $value = $field->deflateValue($value);
            
                if(is_array($value)) {
                    foreach($value as $key => $val) {
                        $rowValues[$key] = $val;
                    }
                } else {
                    $rowValues[$name] = $value;
                }
            }
            
            $values[] = $rowValues;
        }
        
        return $values;
    }
    
    public function deflateReplaceValues(array $row) {
        return $this->deflateInsertValues($row);
    }
    
    public function deflateBatchReplaceValues(array $rows, array &$queryFields) {
        return $this->deflateBatchInsertValues($rows, $queryFields);
    }
    
    public function normalizeReplaceId($originalId, array $row) {
        return $this->normalizeInsertId($originalId, $row);
    }
    
    public function deflateUpdateValues(array $values) {
        $schema = $this->getUnitSchema();
        
        foreach($values as $name => $value) {
            if(!$field = $schema->getField($name)) {
                continue;
            }
            
            if($field instanceof axis\schema\INullPrimitiveField) {
                unset($values[$name]);
                continue;
            }
            
            $value = $field->deflateValue($field->sanitizeValue($value, false));
            
            if(is_array($value)) {
                unset($values[$name]);
                
                foreach($value as $key => $val) {
                    $values[$key] = $val;
                }
            } else {
                $values[$name] = $value;
            }
        }
        
        return $values;
    }
    
    
    
    
// Transactions
    public function beginQueryTransaction() {
        return $this->_adapter->beginQueryTransaction();
    }

    public function commitQueryTransaction() {
        return $this->_adapter->commitQueryTransaction();
    }

    public function rollbackQueryTransaction() {
        return $this->_adapter->rollbackQueryTransaction();
    }
    
    
    
    
    
// Record
    public function newRecord(array $values=null) {
        if($this->_recordClass === null) {
            $this->_recordClass = 'df\\apex\\models\\'.$this->_model->getModelName().'\\'.$this->getUnitName().'\\Record';
            
            if(!class_exists($this->_recordClass)) {
                $this->_recordClass = 'df\\opal\\query\\record\\Base';
            }
        }
        
        return new $this->_recordClass($this, $values, $this->getRecordFieldNames());
    }
    
    public function getRecordPrimaryFieldNames() {
        if(!$index = $this->getUnitSchema()->getPrimaryIndex()) {
            return null;
        }
        
        return array_keys($index->getFields());
    }
    
    public function getRecordFieldNames() {
        return array_keys($this->getUnitSchema()->getFields());
    }
    
    
    
    
    
// Entry point
    public function select($field1=null) {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginSelect(func_get_args())
            ->from($this, $this->getCanonicalUnitName()); 
    }
    
    public function fetch() {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginFetch()
            ->from($this, $this->getCanonicalUnitName());
    }
    
    public function fetchByPrimary($keys) {
        if(!is_array($keys)) {
            $keys = func_get_args();
        }
        
        if(!$index = $this->getUnitSchema()->getPrimaryIndex()) {
            return null;
        }
        
        $query = $this->fetch();
        
        foreach(array_keys($index->getFields()) as $i => $primaryField) {
            $value = array_shift($keys);
            $query->where($primaryField, '=', $value);
        }
        
        return $query->toRow();
    }
    
    public function insert($row) {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginInsert($row)
            ->into($this, $this->getCanonicalUnitName());
    }
    
    public function batchInsert($rows=array()) {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginBatchInsert($rows)
            ->into($this, $this->getCanonicalUnitName());
    }
    
    public function replace($row) {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginReplace($row)
            ->in($this, $this->getCanonicalUnitName());
    }
    
    public function batchReplace($rows=array()) {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginBatchReplace($rows)
            ->in($this, $this->getCanonicalUnitName());
    }
    
    public function update(array $valueMap=null) {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginUpdate($valueMap)
            ->in($this, $this->getCanonicalUnitName());
    }
    
    public function delete() {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginDelete()
            ->from($this, $this->getCanonicalUnitName());
    }
    
    public function begin() {
        return new opal\query\ImplicitSourceTransaction($this->getApplication(), $this);
    }

    
    
// Policy
    public function fetchSubEntity(core\policy\IManager $manager, core\policy\IEntityLocatorNode $node) {
        switch($node->getType()) {
            case 'Schema':
                return $this->getUnitSchema();
        }
    }
    
    
    
// Dump
    public function getDumpProperties() {
        return array(
            'type' => $this->getUnitType(),
            'unitId' => $this->getUnitId(),
            'adapter' => $this->_adapter
        );
    }
}
