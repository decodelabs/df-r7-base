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
use df\mesh;

abstract class Base implements 
    axis\ISchemaBasedStorageUnit, 
    mesh\entity\IActiveParentEntity, 
    opal\query\IEntryPoint,
    opal\query\IIntegralAdapter,
    opal\query\IPaginatingAdapter,
    core\IDumpable {

    use axis\TUnit;
    use axis\TAdapterBasedStorageUnit;
    use axis\TSchemaBasedStorageUnit;

    const NAME_FIELD = null;
    const KEY_NAME = null;
    
    protected static $_defaultRecordClass = 'df\\opal\\record\\Base';
    protected static $_defaultSearchFields = null;
    
    private $_recordClass;
    private $_schema;
    
    public function __construct(axis\IModel $model, $unitName=null) {
        $this->_model = $model;
        $this->_loadAdapter();
    }
    
    public function getUnitType() {
        return 'table';
    }
    
    public function getStorageGroupName() {
        return $this->_adapter->getStorageGroupName();
    }

    public function getStorageBackendName() {
        $output = $this->_model->getModelName().'_'.$this->getCanonicalUnitName();

        if($this->_shouldPrefixNames()) {
            $output = df\Launchpad::$application->getUniquePrefix().'_'.$output;
        }

        return $output;
    }

    public function getRelationUnit($fieldName) {
        $schema = $this->getUnitSchema();
        $field = $schema->getField($fieldName);

        if(!$field instanceof axis\schema\IRelationField) {
            throw new axis\LogicException(
                'Unit '.$this->getUnitId().' does not have a relation field named '.$fieldName
            );
        }

        return $field->getTargetUnit($this->getClusterId());
    }
    
    public function getBridgeUnit($fieldName) {
        $schema = $this->getUnitSchema();
        $field = $schema->getField($fieldName);
        
        if(!$field instanceof axis\schema\IBridgedRelationField) {
            throw new axis\LogicException(
                'Unit '.$this->getUnitId().' does not have a bridge field named '.$fieldName
            );
        }
        
        return $field->getBridgeUnit($this->getClusterId());
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
        
        $cache = axis\schema\Cache::getInstance();
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

    public function getDefinedUnitSchemaVersion() {
        $schema = $this->getTransientUnitSchema();
        $version = $schema->getVersion();

        do {
            $func = '_onUpdateVersion'.$version++;
        } while(method_exists($this, $func));

        return $version -1;
    }
    
    abstract protected function _onCreate(axis\schema\ISchema $schema);
    
    public function validateUnitSchema(axis\schema\ISchema $schema) {
        $schema->validate($this);
        return $this;
    }

    public function ensureStorage() {
        $this->_adapter->ensureStorage();
        return $this;
    }

    public function createStorageFromSchema(axis\schema\ISchema $schema) {
        $this->_adapter->createStorageFromSchema($schema);
        return $this;
    }
    
    public function updateStorageFromSchema(axis\schema\ISchema $schema) {
        $this->_adapter->updateStorageFromSchema($schema);
        return $this;
    }
    
    
    public function destroyStorage() {
        $defUnit = $this->_model->getSchemaDefinitionUnit();
        $defUnit->clearUnitSchemaCache();
        $this->_adapter->destroyStorage();
        $defUnit->remove($this);
        
        return $this;
    }

    public function storageExists() {
        return $this->_adapter->storageExists();
    }
    
    
    
// Query source
    public function getQuerySourceId() {
        return $this->_adapter->getQuerySourceId();
    }

    public function getQuerySourceAdapterHash() {
        return $this->_adapter->getQuerySourceAdapterHash();
    }

    public function getQuerySourceAdapterServerHash() {
        return $this->_adapter->getQuerySourceAdapterServerHash();
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

    public function executeUnionQuery(opal\query\IUnionQuery $query) {
        return $this->_adapter->executeUnionQuery($query);
    }

    public function countUnionQuery(opal\query\IUnionQuery $query) {
        return $this->_adapter->countUnionQuery($query);
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
        
        return $axisField->deflateValue($axisField->sanitizeClauseValue($value));
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

    public function getDefaultSearchFields() {
        $fields = static::$_defaultSearchFields;

        if(empty($fields)) {
            $schema = $this->getUnitSchema();
            $nameField = $this->getRecordNameField();
            $fields = [$nameField => 2];

            if($nameField != 'id' && $schema->hasField('id')) {
                $fields['id'] = 10;
            }

            static::$_defaultSearchFields = $fields;
        }

        return $fields;
    }

    public function getQueryResultValueProcessors(array $fields=null) {
        $schema = $this->getUnitSchema();
        
        if($fields === null) {
            return $schema->getFields();
        }
        
        $output = [];
        
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
                'Query block '.$name.' does not exist on '.$this->getUnitId()
            );
        }

        array_unshift($args, $query);
        call_user_func_array([$this, $method], $args);
        return $this;
    }

    public function applyRelationQueryBlock(opal\query\IQuery $query, $relationField, $name, array $args) {
        $method = 'apply'.ucfirst($name).'RelationQueryBlock';

        if(!method_exists($this, $method)) {
            throw new axis\LogicException(
                'Relation query block '.$name.' does not exist on '.$this->getUnitId()
            );
        }

        array_shift($args);
        array_unshift($args, $query, $relationField);
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
    


// Query blocks
    public function applyLinkRelationQueryBlock(opal\query\IReadQuery $query, $relationField) {
        $schema = $this->getUnitSchema();
        $primaries = $schema->getPrimaryFields();
        $name = $this->getRecordNameField();

        if(!$primaries) {
            throw new axis\LogicException(
                'Unit '.$this->getUnitId().' does not have a primary index'
            );
        }

        $fields = [];
        $combine = [];
        $firstPrimary = null;

        foreach($primaries as $qName => $field) {
            $firstPrimary = $qName;
            $fields[$qName] = $qName.' as '.$relationField.'|'.$qName;
            $combine[$qName] = $relationField.'|'.$qName.' as '.$qName;
        }

        $fields[$name] = $name.' as '.$relationField.'|'.$name;
        $combine[$name] = $relationField.'|'.$name.' as '.$name;


        if($query instanceof opal\query\ISelectQuery) {
            $query->leftJoinRelation($relationField, $fields)
                ->combine($combine)
                    ->nullOn($firstPrimary)
                    ->asOne($relationField)
                ->paginate()
                    ->addOrderableFields($relationField.'|'.$name.' as '.$relationField)
                    ->end();
        } else {
            $query->populateSelect($relationField, array_keys($fields));
        }

        return $this;
    }
    
    
// Entry point
    public function select($field1=null) {
        return opal\query\Initiator::factory()
            ->beginSelect(func_get_args())
            ->from($this, $this->getCanonicalUnitName()); 
    }
    
    public function selectDistinct($field1=null) {
        return opal\query\Initiator::factory()
            ->beginSelect(func_get_args(), true)
            ->from($this, $this->getCanonicalUnitName()); 
    }

    public function union() {
        return opal\query\Initiator::factory()
            ->beginUnion()
            ->with(func_get_args())
            ->from($this);
    }

    public function fetch() {
        return opal\query\Initiator::factory()
            ->beginFetch()
            ->from($this, $this->getCanonicalUnitName());
    }
    
    public function fetchByPrimary($keys) {
        if($keys instanceof opal\record\IRecord
        && $keys->getRecordAdapter() === $this
        && !$keys->isNew()) {
            return $keys;
        }

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
        return opal\query\Initiator::factory()
            ->beginInsert($row)
            ->into($this, $this->getCanonicalUnitName());
    }
    
    public function batchInsert($rows=[]) {
        return opal\query\Initiator::factory()
            ->beginBatchInsert($rows)
            ->into($this, $this->getCanonicalUnitName());
    }
    
    public function replace($row) {
        return opal\query\Initiator::factory()
            ->beginReplace($row)
            ->in($this, $this->getCanonicalUnitName());
    }
    
    public function batchReplace($rows=[]) {
        return opal\query\Initiator::factory()
            ->beginBatchReplace($rows)
            ->in($this, $this->getCanonicalUnitName());
    }
    
    public function update(array $valueMap=null) {
        return opal\query\Initiator::factory()
            ->beginUpdate($valueMap)
            ->in($this, $this->getCanonicalUnitName());
    }
    
    public function delete() {
        return opal\query\Initiator::factory()
            ->beginDelete()
            ->from($this, $this->getCanonicalUnitName());
    }
    
    public function begin() {
        return new opal\query\ImplicitSourceTransaction($this);
    }

    
    
// Mesh
    public function fetchSubEntity(mesh\IManager $manager, array $node) {
        switch($node['type']) {
            case 'Record':
                return $this->fetchByPrimary($node['id']);

            case 'Schema':
                return $this->getUnitSchema();
        }
    }

    public function getSubEntityLocator(mesh\entity\IEntity $entity) {
        if($entity instanceof opal\record\IPrimaryKeySetProvider) {
            $output = 'axis://';

            if($clusterId = $this->getClusterId()) {
                $output .= $clusterId.'/';
            }

            $output .= $this->getModel()->getModelName().'/'.ucfirst($this->getUnitName());
            $output = new mesh\entity\Locator($output);
            $id = $entity->getPrimaryKeySet()->getEntityId();
            $output->setId($id);

            return $output;
        }

        throw new mesh\entity\UnexpectedValueException(
            'Unknown entity type'
        );
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'type' => $this->getUnitType(),
            'unitId' => $this->getUnitId(),
            'adapter' => $this->_adapter
        ];
    }
}
