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

abstract class Base implements 
    axis\ISchemaBasedStorageUnit, 
    core\policy\IActiveParentEntity, 
    opal\query\IEntryPoint,
    opal\query\IIntegralAdapter,
    opal\query\IPaginatingAdapter,
    core\IDumpable {

    use axis\TUnit;
    use axis\TAdapterBasedStorageUnit;
    
    protected static $_defaultRecordClass = 'df\\opal\\record\\Base';
    
    private $_recordClass;
    private $_schema;
    
    public function __construct(axis\IModel $model, $unitName=null) {
        $this->_model = $model;
        $this->_loadAdapter();
    }
    
    public function getUnitType() {
        return 'table';
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
        $defUnit = $this->_model->getSchemaDefinitionUnit();
        $defUnit->clearUnitSchemaCache();
        $this->_adapter->destroyStorage();
        $defUnit->remove($this);
        
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

    public function ensureStorageConsistency() {
        $this->_adapter->ensureStorageConsistency();
        return $this;
    }
    
    public function handleQueryException(opal\query\IQuery $query, \Exception $e) {
        return $this->_adapter->handleQueryException($query, $e);
    }
    
    public function applyPagination(opal\query\IPaginator $paginator) {
        $schema = $this->getUnitSchema();
        $default = null;

        foreach($schema->getFields() as $name => $field) {
            if($field instanceof opal\schema\INullPrimitiveField
            || $field instanceof opal\schema\IManyRelationField) {
                continue;
            }

            if($default === null
            || $default == 'id ASC') {
                if(in_array($name, ['id', 'name', 'title'])) {
                    $default = $name.' ASC';
                } else if($name == 'date') {
                    $default = 'date DESC';
                }
            }

            $fields[] = $name;
        }

        if(!empty($fields)) {
            $paginator->addOrderableFields($fields);
        }

        if($default !== null) {
            $paginator->setDefaultOrder($default);
        }

        return $this;
    }
    
    
// Query proxy
    public function executeSelectQuery(opal\query\ISelectQuery $query) {
        return $this->_adapter->executeSelectQuery($query);
    }
    
    public function countSelectQuery(opal\query\ISelectQuery $query) {
        return $this->_adapter->countSelectQuery($query);
    }
    
    public function executeFetchQuery(opal\query\IFetchQuery $query) {
        return $this->_adapter->executeFetchQuery($query);
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

    

// Integral adapter
    public function getQueryAdapterSchema() {
        return $this->getUnitSchema();
    }

    public function prepareQueryClauseValue(opal\query\IField $field, $value) {
        $schema = $this->getUnitSchema();

        if(!$axisField = $schema->getField($field->getName())) {
            return $value;
        }
        
        return $axisField->deflateValue($axisField->sanitizeValue($value));
    }
    
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        if((!$axisField = $this->getUnitSchema()->getField($field->getName()))
         || !$axisField instanceof opal\schema\IQueryClauseRewriterField) {
            throw new axis\schema\RuntimeException(
                'Query field '.$field->getName().' has no virtual field rewriter'
            );
        }
        
        return $axisField->rewriteVirtualQueryClause($parent, $field, $operator, $value, $isOr);
    }

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
    

    public function applyQueryBlock(opal\query\IQuery $query, $name, array $args) {
        $method = 'apply'.ucfirst($name).'QueryBlock';

        if(!method_exists($this, $method)) {
            throw new axis\LogicException(
                'Query block '.$name.' does not exist'
            );
        }

        array_unshift($args, $query);
        call_user_func_array([$this, $method], $args);
        return $this;
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
        
        return new $this->_recordClass($this, $values, array_keys($this->getUnitSchema()->getFields()));
    }

    public function newPartial(array $values=null) {
        return new opal\record\Partial($this, $values);
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
        $primaryKeySet = null;

        if(is_string($keys) && substr($keys, 0, 9) == 'keySet?') {
            $primaryKeySet = opal\record\PrimaryKeySet::fromEntityId($keys);
        } else if($keys instanceof opal\record\IPrimaryKeySet) {
            $primaryKeySet = $keys;
        }

        if($primaryKeySet) {
            foreach($primaryKeySet->toArray() as $key => $value) {
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
            $output = new core\policy\entity\Locator('axis://'.$this->getModel()->getModelName().'/'.ucfirst($this->getUnitName()));
            $id = $entity->getPrimaryKeySet()->getEntityId();
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
