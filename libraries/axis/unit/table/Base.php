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
    core\policy\IActiveParentEntity, 
    opal\query\IEntryPoint,
    opal\query\IIntegralAdapter,
    core\IDumpable {
    
    protected static $_defaultRecordClass = 'df\\opal\\query\\record\\Base';
    
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
        
        if(!$field instanceof axis\schema\IBridgedRelationField) {
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
        $this->clearUnitSchemaCache();
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
            if($field instanceof axis\schema\INullPrimitiveField) {
                continue;
            }
         
            $output[] = $this->extrapolateQuerySourceField($source, $name, null, $field);
        }

        return $output;
    }
    
    public function extrapolateQuerySourceField(opal\query\ISource $source, $name, $alias=null, opal\schema\IField $field=null) {
        // Get primary
        if($name == '@primary') {
            $schema = $this->getUnitSchema();

            if(!$primaryIndex = $schema->getPrimaryIndex()) {
                throw new axis\schema\RuntimeException(
                    'Unit '.$this->getUnitId().' does not had a primary index'
                );
            }

            $fields = array();

            foreach($primaryIndex->getFields() as $fieldName => $indexField) {
                $fields[] = $this->extrapolateQuerySourceFieldFromSchemaField($source, $fieldName, $fieldName, $indexField);
            }

            return new opal\query\field\Virtual($source, $name, $alias, $fields);
        }


        // Dereference from source manager
        if($field === null) {
            if(!$field = $this->getUnitSchema()->getField($name)) {
                return new opal\query\field\Intrinsic($source, $name, $alias);
            }
        }
        
        // Generic
        return $this->extrapolateQuerySourceFieldFromSchemaField($source, $name, $alias, $field);
    }

    public function extrapolateQuerySourceFieldFromSchemaField(opal\query\ISource $source, $name, $alias, axis\schema\IField $field) {
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


// Populates
    public function getPopulateQuerySourceAdapter(opal\query\ISourceManager $sourceManager, $fieldName) {
        $schema = $this->getUnitSchema();
        $field = $schema->getField($fieldName);

        if(!$field instanceof axis\schema\IRelationField) {
            throw new axis\RuntimeException(
                'Unit '.$this->getUnitId().' does not had a populatable relation field named '.$fieldName
            );
        }

        //if($field instanceof axis\schema\IBridgedRelationField) {
            //$id = $field->getBridgeUnitId();
            //$alias = $fieldName.'_bridge';
            //$fields = ['*'];
        //} else {
            $id = $field->getTargetUnitId();
            $alias = uniqid('ppl_'.$fieldName);
            $fields = ['*'];
        //}

        $adapter = axis\Unit::fromId($id, $this->_model->getApplication());
        return $sourceManager->newSource($adapter, $alias, $fields);
    }

    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate) {
        $fieldName = $populate->getFieldName();
        $schema = $this->getUnitSchema();
        $field = $schema->getField($fieldName);

        return $field->rewritePopulateQueryToAttachment($populate);
    }


    public function rewriteCountRelationCorrelation(opal\query\ICorrelatableQuery $query, $fieldName, $alias) {
        $schema = $this->getTransientUnitSchema();
        $field = $schema->getField($fieldName);

        if(!$field instanceof axis\schema\IManyRelationField) {
            throw new axis\schema\FieldTypeNotFoundException(
                $fieldName.' is not a many relation field'
            );
        }

        $application = $query->getSourceManager()->getApplication();
        $fieldName = $field->getName();

        if($field instanceof axis\schema\IBridgedRelationField) {
            // Field is bridged
            
            $bridgeAlias = $fieldName.'Bridge';
            $localAlias = $query->getSource()->getAlias();
            $localName = $field->getBridgeLocalFieldName();
            $targetName = $field->getBridgeTargetFieldName();

            $query->correlate('COUNT('.$bridgeAlias.'.'.$targetName.')', $alias)
                ->from($this->getBridgeUnit($fieldName), $bridgeAlias)
                ->on($bridgeAlias.'.'.$localName, '=', $localAlias.'.@primary')
                ->endCorrelation();
        } else {
            // Field is OneToMany
            $targetUnit = $field->getTargetUnit($application);
            $targetAlias = $fieldName.'Count';
            $targetFieldName = $field->getTargetField();
            $localName = $this->getUnitName();

            $query->correlate('COUNT('.$targetAlias.'.@primary)', $alias)
                ->from($targetUnit, $targetAlias)
                ->on($targetAlias.'.'.$targetFieldName, '=', $localName.'.@primary')
                ->endCorrelation();
        }

        return $this;
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
        $name = $field->getName();

        if($name{0} == '@') {
            switch(strtolower($name)) {
                case '@primary':
                    $fieldList = array_keys($this->getUnitSchema()->getPrimaryIndex()->getFields());
                    return $this->mapVirtualClause($parent, $field, $operator, $value, $isOr, $fieldList);

                default:
                    throw new axis\schema\RuntimeException(
                        'Query field '.$field->getName().' has no virtual field rewriter'
                    );
            }
        }

        if((!$axisField = $this->getUnitSchema()->getField($field->getName()))
         || !$axisField instanceof axis\schema\IQueryClauseRewriterField) {
            throw new axis\schema\RuntimeException(
                'Query field '.$field->getName().' has no virtual field rewriter'
            );
        }
        
        return $axisField->rewriteVirtualQueryClause($parent, $field, $operator, $value, $isOr);
    }

    public function mapVirtualClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr, array $fieldList, $localPrefix=null) {
        $fieldCount = count($fieldList);
        $targetPrefix = null;

        $output = null;

        if($fieldCount > 1) {
            $output = new opal\query\clause\ListBase($parent, $isOr);
            $clauseFactory = $output;
        } else {
            $clauseFactory = $parent;
        }

        if($value instanceof opal\record\IRecord) {
            $value = $value->getPrimaryManifest();
        }
        
        if($value instanceof opal\query\IVirtualField) {
            $targetPrefix = $value->getName();

            if($targetPrefix{0} == '@') {
                $targetPrefix = null;
            }

            $valueFields = array();

            foreach($value->dereference() as $targetField) {
                $targetFieldName = $targetField->getName();

                if($localPrefix === null && $targetPrefix !== null) {
                    $targetFieldName = substr($targetFieldName, strlen($targetPrefix) + 1);
                } else if($localPrefix !== null && $targetPrefix === null) {
                    $targetFieldName = $localPrefix.'_'.$targetFieldName;
                }

                $valueFields[$targetFieldName] = $targetField;
            }
        } else if($value instanceof opal\record\IPrimaryManifest) {
            $value = $value->getIntrinsicFieldMap($localPrefix);
        } else if(is_scalar($value) && $fieldCount > 1) {
            throw new axis\schema\RuntimeException(
                'KeyGroup fields do not match on '.
                $parent->getSource()->getAdapter()->getName().':'.$field->getName()
            );
        }


        foreach($fieldList as $fieldName) {
            $subValue = null;
            $keyName = $fieldName;

            if($localPrefix !== null) {
                $keyName = $localPrefix.'_'.$keyName;
            }


            if($value instanceof opal\query\IVirtualField) {
                if(!isset($valueFields[$keyName])) {
                    throw new axis\schema\RuntimeException(
                        'KeyGroup join fields do not match between '.
                        $parent->getSource()->getAdapter()->getUnitId().':'.$field->getName().' and '.
                        $value->getSource()->getAdapter()->getUnitId().':'.$value->getName().
                        ' for keyName '.$keyName
                    );
                }
                
                $subValue = $valueFields[$keyName];
            } else if($operator != opal\query\clause\Clause::OP_IN
                   && $operator != opal\query\clause\Clause::OP_NOT_IN
                   && is_array($value)) {

                // this does not cover all eventualities
                // in [[id_a, id_b], [id_a, id_b]]
                // need to check operator further up

                if(!array_key_exists($keyName, $value)) {
                    throw new axis\schema\RuntimeException(
                        'KeyGroup fields do not match on '.
                        $parent->getSource()->getAdapter()->getUnitId().':'.$field->getName().
                        ' for keyName '.$keyName
                    );
                }
                
                $subValue = $value[$keyName];
            } else {
                $subValue = $value;
            }

            $newField = new opal\query\field\Intrinsic($field->getSource(), $keyName);
            $clause = opal\query\clause\Clause::factory($clauseFactory, $newField, $operator, $subValue, $output ? false : $isOr);

            if($output) {
                $output->_addClause($clause);
            } else {
                return $clause;
            }
        }
        
        return $output;
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
                $values[$name] = $field->inflateValueFromRow($name, $row, null);
            }
        }
        
        return new opal\record\PrimaryManifest($fields, $values);
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
                $this->_recordClass = static::$_defaultRecordClass;
            } else if(!is_subclass_of($this->_recordClass, static::$_defaultRecordClass)) {
                throw new axis\LogicException(
                    $this->_recordClass.' is not a valid record class for unit '.$this->getUnitId()
                );            
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
    
    public function selectDistinct($field1=null) {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginSelect(func_get_args(), true)
            ->from($this, $this->getCanonicalUnitName()); 
    }

    public function fetch() {
        return opal\query\Initiator::factory($this->getApplication())
            ->beginFetch()
            ->from($this, $this->getCanonicalUnitName());
    }
    
    public function fetchByPrimary($keys) {
        $query = $this->fetch();
        $primaryManifest = null;

        if(is_string($keys) && substr($keys, 0, 9) == 'manifest?') {
            $primaryManifest = opal\record\PrimaryManifest::fromEntityId($keys);
        } else if($keys instanceof opal\record\IPrimaryManifest) {
            $primaryManifest = $keys;
        }

        if($primaryManifest) {
            foreach($primaryManifest->toArray() as $key => $value) {
                $query->where($key, '=', $value);
            }
        } else {
            if(!is_array($keys)) {
                $keys = func_get_args();
            }
            
            if(!$index = $this->getUnitSchema()->getPrimaryIndex()) {
                return null;
            }
            
            foreach(array_keys($index->getFields()) as $i => $primaryField) {
                $value = array_shift($keys);
                $query->where($primaryField, '=', $value);
            }
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
            case 'Record':
                return $this->fetchByPrimary($node->getId());

            case 'Schema':
                return $this->getUnitSchema();
        }
    }

    public function getSubEntityLocator(core\policy\IEntity $entity) {
        if($entity instanceof opal\record\IRecord) {
            $output = new core\policy\EntityLocator('axis://'.$this->getModel()->getModelName().'/'.ucfirst($this->getUnitName()));
            $id = $entity->getPrimaryManifest()->getEntityId();
            $output->setId($id);

            return $output;
        }

        throw new core\policy\UnexpectedValueException(
            'Unknown entity type'
        );
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
