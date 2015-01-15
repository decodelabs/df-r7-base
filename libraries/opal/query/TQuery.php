<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;
use df\user;


trait TQuery_AdapterAware {
    
    protected $_adapter;
    private $_adapterHash;
    private $_adapterServerHash;
    
    public function getAdapter() {
        return $this->_adapter;
    }
    
    public function getAdapterHash() {
        if($this->_adapterHash === null) {
            $this->_adapterHash = $this->_adapter->getQuerySourceAdapterHash();
        }
        
        return $this->_adapterHash; 
    }

    public function getAdapterServerHash() {
        if($this->_adapterServerHash === null) {
            $this->_adapterServerHash = $this->_adapter->getQuerySourceAdapterServerHash();
        }

        return $this->_adapterServerHash;
    }
}


trait TQuery_TransactionAware {
    
    protected $_transaction;
    
    public function setTransaction(ITransaction $transaction=null) {
        $this->_transaction = $transaction;
        return $this;
    }
    
    public function getTransaction() {
        return $this->_transaction;
    }
}


trait TQuery_ParentAware {
    
    protected $_parent;
    
    public function getParentQuery() {
        return $this->_parent;
    }

    public function getParentSourceManager() {
        return $this->_parent->getSourceManager();
    }
    
    public function getParentSource() {
        return $this->_parent->getSource();
    }

    public function getParentSourceAlias() {
        return $this->_parent->getSourceAlias();
    }

    public function isSourceDeepNested(ISource $source) {
        if(!$this->_parent instanceof IParentQueryAware) {
            return false;
        }
        $gp = $this->_parent;
        $sourceId = $source->getId();

        do {
            $gp = $gp->getParentQuery();

            if($gp->getSource()->getId() == $sourceId) {
                return true;
            }

        } while($gp instanceof IParentQueryAware);

        return false;
    }
}


trait TQuery_NestedComponent {

    protected $_nestedParent;

    public function setNestedParent($parent) {
        $this->_nestedParent = $parent;
        return $this;
    }

    public function getNestedParent() {
        if($this->_nestedParent) {
            return $this->_nestedParent;
        }

        if($this instanceof IParentQueryAware) {
            return $this->getParentQuery();
        }

        return null;
    }
}



/*************************
 * Base
 */
trait TQuery {

    use user\TAccessLock;
    use core\lang\TChainable;
    
    public function getAccessLockDomain() {
        return $this->getSource()->getAdapter()->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action=null) {
        return $this->getSource()->getAdapter()->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null) {
        return $this->getSource()->getAdapter()->getDefaultAccess($action);
    }

    public function getAccessLockId() {
        return $this->getSource()->getAdapter()->getAccessLockId();
    }

    protected function _newQuery() {
        $sourceManager = $this->getSourceManager();

        return Initiator::factory()
            ->setTransaction($sourceManager->getTransaction());
    }


    public function importBlock($name) {
        if(preg_match('/(.+)\.(.+)$/', $name, $matches)) {
            $source = $this->getSourceManager()->getSourceByAlias($matches[1]);

            if(!$source) {
                throw new InvalidArgumentException(
                    'Cannot import query block - adapter source '.$matches[1].' could not be found'
                );
            }

            $name = $matches[2];
        } else {
            $source = $this->getSource();
        }

        $adapter = $source->getAdapter();

        if(!$adapter instanceof opal\query\IIntegralAdapter) {
            throw new LogicException(
                'Cannot import query block - adapter is not integral'
            );
        }

        $adapter->applyQueryBlock($this, $name, array_slice(func_get_args(), 1));
        return $this;
    }

    public function importRelationBlock($relationField, $name) {
        $field = $this->_lookupRelationField($relationField, $clusterId);

        if(preg_match('/(.+)\.(.+)$/', $name, $matches)) {
            $source = $this->getSourceManager()->getSourceByAlias($matches[1]);

            if(!$source) {
                throw new InvalidArgumentException(
                    'Cannot import query block - adapter source '.$matches[1].' could not be found'
                );
            }

            $name = $matches[2];
            $adapter = $source->getAdapter();
        } else {
            $adapter = $field->getTargetQueryAdapter($clusterId);
        }

        if(!$adapter instanceof opal\query\IIntegralAdapter) {
            throw new LogicException(
                'Cannot import query block - adapter is not integral'
            );
        }

        $adapter->applyRelationQueryBlock($this, $field->getName(), $name, array_slice(func_get_args(), 1));
        return $this;
    }


    public function setTransaction(ITransaction $transaction=null) {
        $this->getSourceManager()->setTransaction($transaction);
        return $this;
    }

    public function getTransaction() {
        return $this->getSourceManager()->getTransaction();
    }

    protected function _lookupRelationField($fieldName, &$clusterId, &$queryField=null) {
        $source = $this->getSource();
        $field = null;

        if(false === strpos($fieldName, '.')) {
            $sourceAdapter = $source->getAdapter();

            if($sourceAdapter instanceof IIntegralAdapter) {
                $schema = $sourceAdapter->getQueryAdapterSchema();
                $field = $schema->getField($fieldName);

                if(!$field instanceof opal\schema\IRelationField) {
                    $field = null;
                } else {
                    $queryField = $source->extrapolateIntegralAdapterField($fieldName);
                }
            }
        }

        if(!$field) {
            $sourceManager = $this->getSourceManager();
            $queryField = $sourceManager->extrapolateIntrinsicField($source, $fieldName, true);

            $source = $queryField->getSource();
            $sourceAdapter = $source->getAdapter();

            if(!$sourceAdapter instanceof IIntegralAdapter) {
                throw new LogicException(
                    'Source adapter is not integral and does not have relation meta data'
                );
            }

            $schema = $sourceAdapter->getQueryAdapterSchema();
            $field = $schema->getField($queryField->getName());

            if(!$field instanceof opal\schema\IRelationField) {
                throw new opal\query\InvalidArgumentException(
                    $fieldName.' is not a relation field'
                );
            }
        }

        if(!$field->isOnGlobalCluster()) {
            $clusterId = $sourceAdapter->getClusterId();
        }

        return $field;
    }
}


trait TQuery_LocalSource {

    protected $_sourceManager;
    protected $_source;
    
    public function getSourceManager() {
        return $this->_sourceManager;
    }
    
    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }
}



/****************************
 * Derivable
 */
trait TQuery_Derivable {

    protected $_derivationParentInitiator;

    public function setDerivationParentInitiator(IInitiator $initiator) {
        $this->_derivationParentInitiator = $initiator;
        return $this;
    }

    public function getDerivationParentInitiator() {
        return $this->_derivationParentInitiator;
    }

    public function getDerivationSourceAdapter() {
        return $this->getSource()->getAdapter();
    }

    public function endSource() {
        if(!$this->_derivationParentInitiator) {
            throw new LogicException(
                'Cannot create derived source - no parent initiator has been created'
            );
        }

        $adapter = new DerivedSourceAdapter($this);

        if(!$adapter->supportsQueryType(IQueryTypes::DERIVATION)) {
            throw new LogicException(
                'Query adapter '.$adapter->getQuerySourceDisplayName().' does not support derived tables'
            );
        }

        $output = $this->_derivationParentInitiator->from($adapter, uniqid('drv_'));
        $this->_derivationParentInitiator = null;
        return $output;
    }
}




/****************************
 * Locational
 */
trait TQuery_Locational {

    protected $_location;
    protected $_searchChildLocations = false;

    public function inside($location, $searchChildLocations=false) {
        if(!$this->getSource()->getAdapter()->supportsQueryFeature(IQueryFeatures::LOCATION)) {
            throw new LogicException(
                'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().
                ' does not support location base queries'
            );
        }

        $this->_location = $location;
        $this->_searchChildLocations = (bool)$searchChildLocations;
        return $this;
    }

    public function getLocation() {
        return $this->_location;
    }

    public function shouldSearchChildLocations($flag=null) {
        if($flag !== null) {
            $this->_searchChildLocations = (bool)$flag;
            return $this;
        }

        return $this->_searchChildLocations;
    }
}



/****************************
 * Distinct
 */
trait TQuery_Distinct {

    protected $_isDistinct = false;

    public function isDistinct($flag=null) {
        if($flag !== null) {
            $this->_isDistinct = (bool)$flag;
            return $this;
        }

        return $this->_isDistinct;
    }
}



 /****************************
 * Correlations
 */

trait TQuery_Correlatable {


    public function correlate($field, $alias=null) {
        return $this->_newQuery()->beginCorrelation($this, $field, $alias);
    }

    public function countRelation($field, $alias=null) {
        return $this->_beginRelationCorrelation($field, $alias, 'COUNT')->endCorrelation();
    }

    public function beginCountRelation($field, $alias=null) {
        return $this->_beginRelationCorrelation($field, $alias, 'COUNT');
    }

    public function hasRelation($field, $alias=null) {
        return $this->_beginRelationCorrelation($field, $alias, 'HAS')->endCorrelation();
    }

    public function beginHasRelation($field, $alias=null) {
        return $this->_beginRelationCorrelation($field, $alias, 'HAS');
    }

    protected function _beginRelationCorrelation($fieldName, $alias, $aggregateType) {
        if($alias === null) {
            $alias = $fieldName;
        }

        $field = $this->_lookupRelationField($fieldName, $clusterId, $queryField);

        if(!$field instanceof opal\schema\IManyRelationField) {
            throw new opal\query\InvalidArgumentException(
                'Cannot begin relation correlation - '.$fieldName.' is not a many relation field'
            );
        }

        $source = $queryField->getSource();
        $fieldAlias = $alias ? $alias : $fieldName;

        if($field instanceof opal\schema\IBridgedRelationField) {
            // Field is bridged
            $bridgeAdapter = $field->getBridgeQueryAdapter($clusterId);
            $bridgeAlias = $fieldAlias.'Bridge';
            $localAlias = $source->getAlias();
            $localName = $field->getBridgeLocalFieldName();
            $targetName = $field->getBridgeTargetFieldName();

            $correlation = $this->correlate($aggregateType.'('.$bridgeAlias.'.'.$targetName.')', $alias)
                ->from($bridgeAdapter, $bridgeAlias)
                ->on($bridgeAlias.'.'.$localName, '=', $localAlias.'.@primary');
        } else {
            // Field is OneToMany (hopefully!)
            $targetAdapter = $field->getTargetQueryAdapter($clusterId);
            $targetAlias = $fieldAlias;
            $targetFieldName = $field->getTargetField();
            $localAlias = $source->getAlias();

            $correlation = $this->correlate($aggregateType.'('.$targetAlias.'.@primary)', $alias)
                ->from($targetAdapter, $targetAlias)
                ->on($targetAlias.'.'.$targetFieldName, '=', $localAlias.'.@primary');
        }

        $correlation->endCorrelation();
        return $correlation;
    }

    public function addCorrelation(ICorrelationQuery $correlation) {
        $source = $this->getSource();
        $adapter = $source->getAdapter();

        if(!$adapter->supportsQueryType($correlation->getQueryType())) {
            throw new LogicException(
                'Query adapter '.$adapter->getQuerySourceDisplayName().' does not support correlations'
            );
        }
        
        $field = new opal\query\field\Correlation($correlation);
        $source->addOutputField($field);

        return $this;
    }

    public function getCorrelations() {
        $source = $this->getSource();
        $output = [];

        foreach($source->getOutputFields() as $name => $field) {
            if($field instanceof ICorrelationField) {
                $output[$name] = $field;
            }
        }

        return $output;
    }
}



 /****************************
 * Joins
 */
trait TQuery_Joinable {
    
    protected $_joins = [];
    
// Inner
    public function join($field1=null) {
        return $this->_newQuery()->beginJoin($this, func_get_args(), IJoinQuery::INNER);
    }

    public function joinRelation($relationField, $field1=null) {
        return $this->_beginJoinRelation($relationField, array_slice(func_get_args(), 1), IJoinQuery::INNER)->endJoin();
    }

    public function beginJoinRelation($relationField, $field1=null) {
        return $this->_beginJoinRelation($relationField, array_slice(func_get_args(), 1), IJoinQuery::INNER);
    }
    

// Left
    public function leftJoin($field1=null) {
        return $this->_newQuery()->beginJoin($this, func_get_args(), IJoinQuery::LEFT);
    }

    public function leftJoinRelation($relationField, $field1=null) {
        return $this->_beginJoinRelation($relationField, array_slice(func_get_args(), 1), IJoinQuery::LEFT)->endJoin();
    }

    public function beginLeftJoinRelation($relationField, $field1=null) {
        return $this->_beginJoinRelation($relationField, array_slice(func_get_args(), 1), IJoinQuery::LEFT);
    }
    

// Right
    public function rightJoin($field1=null) {
        return $this->_newQuery()->beginJoin($this, func_get_args(), IJoinQuery::RIGHT);
    }

    public function rightJoinRelation($relationField, $field1=null) {
        return $this->_beginJoinRelation($relationField, array_slice(func_get_args(), 1), IJoinQuery::RIGHT)->endJoin();
    }

    public function beginRightJoinRelation($relationField, $field1=null) {
        return $this->_beginJoinRelation($relationField, array_slice(func_get_args(), 1), IJoinQuery::RIGHT);
    }


    protected function _beginJoinRelation($fieldName, array $targetFields, $joinType=IJoinQuery::INNER) {
        $field = $this->_lookupRelationField($fieldName, $clusterId);
        $join = $this->_newQuery()->beginJoin($this, $targetFields, $joinType);

        $targetAlias = 'jrl_'.str_replace('.', '_', $fieldName);

        if($field instanceof opal\schema\IBridgedRelationField) {
            // Field is bridged
            core\stub($field);
            $bridgeAdapter = $field->getBridgeQueryAdapter($clusterId);
            $bridgeAlias = $fieldName.'Bridge';
            $localAlias = $source->getAlias();
            $localName = $field->getBridgeLocalFieldName();
            $targetName = $field->getBridgeTargetFieldName();

            $correlation = $this->correlate($aggregateType.'('.$bridgeAlias.'.'.$targetName.')', $alias)
                ->from($bridgeAdapter, $bridgeAlias)
                ->on($bridgeAlias.'.'.$localName, '=', $localAlias.'.@primary');
        } else if($field instanceof opal\schema\IManyRelationField) {
            // Field is OneToMany
            core\stub($field);
            $targetAdapter = $field->getTargetQueryAdapter($clusterId);
            $targetAlias = $fieldName;
            $targetFieldName = $field->getTargetField();
            $localAlias = $source->getAlias();

            $correlation = $this->correlate($aggregateType.'('.$targetAlias.'.@primary)', $alias)
                ->from($targetAdapter, $targetAlias)
                ->on($targetAlias.'.'.$targetFieldName, '=', $localAlias.'.@primary');
        } else {
            // Field is One
            $targetAdapter = $field->getTargetQueryAdapter($clusterId);
            
            $join = $join->from($targetAdapter, $targetAlias)
                ->on($targetAlias.'.@primary', '=', $fieldName);
        }

        return $join;
    }

    
    public function addJoin(IJoinQuery $join) {
        $source = $this->getSource();

        if(!$source->getAdapter()->supportsQueryType($join->getQueryType())) {
            throw new LogicException(
                'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' does not support joins'
            );
        }
        
        $this->_joins[$join->getSourceAlias()] = $join;
        return $this;
    }
    
    public function getJoins() {
        return $this->_joins;
    }
    
    public function clearJoins() {
        $sourceManager = $this->getSourceManager();

        foreach($this->_joins as $sourceAlias => $join) {
            $sourceManager->removeSource($sourceAlias);
        }
        
        $this->_joins = [];
        return $this;
    }
}



trait TQuery_JoinConstrainable {
    
    protected $_joinConstraints = [];
    
    public function joinConstraint() {
        return $this->_newQuery()->beginJoinConstraint($this, IJoinQuery::INNER);
    }
    
    public function leftJoinConstraint() {
        return $this->_newQuery()->beginJoinConstraint($this, IJoinQuery::LEFT);
    }
    
    public function rightJoinConstraint() {
        return $this->_newQuery()->beginJoinConstraint($this, IJoinQuery::RIGHT);
    }
    
    public function addJoinConstraint(IJoinConstraintQuery $join) {
        $source = $this->getSource();

        if(!$source->getAdapter()->supportsQueryType($join->getQueryType())) {
            throw new LogicException(
                'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' does not support joins'
            );
        }
        
        $this->_joinConstraints[$join->getSourceAlias()] = $join;
        return $this;
    }
    
    public function getJoins() {
        return $this->_joinConstraints;
    }
    
    public function clearJoins() {
        $sourceManager = $this->getSourceManager();

        foreach($this->_joinConstraints as $sourceAlias => $join) {
            $sourceManager->removeSource($sourceAlias);
        }
        
        $this->_joinConstraints = [];
        return $this;
    }
}



trait TQuery_JoinClauseFactoryBase {
    
    protected $_joinClauseList;
    
    public function beginOnClause() {
        return new opal\query\clause\JoinList($this);
    }

    public function beginOrOnClause() {
        return new opal\query\clause\JoinList($this, true);
    }

    
    public function addJoinClause(opal\query\IJoinClauseProvider $clause=null) {
        $this->getJoinClauseList()->addJoinClause($clause);
        return $this;
    }

    public function getJoinClauseList() {
        if(!$this->_joinClauseList) {
            $this->_joinClauseList = new opal\query\clause\JoinList($this);
        }
        
        return $this->_joinClauseList;
    }
    
    public function hasJoinClauses() {
        return !empty($this->_joinClauseList) 
            && !$this->_joinClauseList->isEmpty();
    }
    
    public function clearJoinClauses() {
        if($this->_joinClauseList) {
            $this->_joinClauseList->clearJoinClauses();
        }
        
        return $this;
    }
    
    public function getNonLocalFieldReferences() {
        if($this->_joinClauseList) {
            return $this->_joinClauseList->getNonLocalFieldReferences();
        }

        return [];
    }
    
    public function referencesSourceAliases(array $sourceAliases) {
        if($this->_joinClauseList) {
            return $this->_joinClauseList->referencesSourceAliases($sourceAliases);
        }
        
        return false;
    }
}

trait TQuery_ParentAwareJoinClauseFactory {
    
    use TQuery_JoinClauseFactoryBase;
    
    public function on($localField, $operator, $foreignField) {
        $source = $this->getSource();
        $sourceManager = $this->getSourceManager();

        $this->getJoinClauseList()->addJoinClause(
            opal\query\clause\Clause::factory(
                $this,
                $sourceManager->extrapolateIntrinsicField($source, $localField, true),
                $operator,
                $sourceManager->extrapolateIntrinsicField(
                    $this->_parent->getSource(), 
                    $foreignField, 
                    $source->getAlias()
                ),
                false
            )
        );
        
        return $this;
    }

    public function orOn($localField, $operator, $foreignField) {
        $source = $this->getSource();
        $sourceManager = $this->getSourceManager();

        $this->getJoinClauseList()->addJoinClause(
            opal\query\clause\Clause::factory(
                $this,
                $sourceManager->extrapolateIntrinsicField($source, $localField, true),
                $operator,
                $sourceManager->extrapolateIntrinsicField(
                    $this->_parent->getSource(), 
                    $foreignField, 
                    $source->getAlias()
                ),
                true
            )
        );
        
        return $this;
    }
}



/*****************************
 * Populate
 */
trait TQuery_Populatable {
    
    protected $_populates = [];

    public function populate($field1) {
        return $this->_newQuery()->beginPopulate($this, func_get_args(), IPopulateQuery::TYPE_ALL)->endPopulate();
    }

    public function populateSelect($populateField, $targetField1=null) {
        $fields = func_get_args();

        return $this->_newQuery()->beginPopulate($this, [array_shift($fields)], IPopulateQuery::TYPE_ALL, $fields)
            ->isSelect(true)
            ->endPopulate();
    }

    public function populateSome($field) {
        return $this->_newQuery()->beginPopulate($this, [$field], IPopulateQuery::TYPE_SOME);
    }

    public function populateSelectSome($populateField, $targetField1=null) {
        $fields = func_get_args();

        return $this->_newQuery()->beginPopulate($this, [array_shift($fields)], IPopulateQuery::TYPE_SOME, $fields)
            ->isSelect(true);
    }

    public function addPopulate(IPopulateQuery $populate) {
        $this->_populates[$populate->getFieldName()] = $populate;
        $this->getSource()->addOutputField($populate->getField());

        return $this;
    }

    public function getPopulate($fieldName) {
        if(isset($this->_populates[$fieldName])) {
            return $this->_populates[$fieldName];
        }
    }

    public function getPopulates() {
        return $this->_populates;
    }

    public function clearPopulates() {
        $this->_populates = [];
        return $this;
    }

}


trait TQuery_Populate {

    use TQuery_ParentAware;
    use TQuery_NestedComponent;

    protected $_field;
    protected $_type;
    protected $_isSelect = false;

    public function getQueryType() {
        return IQueryTypes::POPULATE;
    }

    public function getField() {
        return $this->_field;
    }

    public function getFieldName() {
        return $this->_field->getName();
    }

    public function getPopulateType() {
        return $this->_type;
    }

    public function isSelect($flag=null) {
        if($flag !== null) {
            $this->_isSelect = (bool)$flag;
            return $this;
        }

        return $this->_isSelect;
    }

    public function endPopulate() {
        $this->_parent->addPopulate($this);
        return $this->getNestedParent();
    }
}





/*************************
 * Combine
 */
trait TQuery_Combinable {

    protected $_combines = [];

    public function combine($field1) {
        return $this->_newQuery()->beginCombine($this, func_get_args());
    }

    public function addCombine($name, ICombineQuery $combine) {
        $this->_combines[$name] = $combine;
        return $this;
    }

    public function getCombines() {
        return $this->_combines;
    }

    public function clearCombines() {
        $this->_combines = [];
        return $this;
    }
}


trait TQuery_Combine {

    use TQuery_ParentAware;
    use TQuery_NestedComponent;

    protected $_fields = [];
    protected $_nullFields = [];
    protected $_isCopy = false;

    public function getQueryType() {
        return IQueryTypes::COMBINE;
    }

    public function setFields($fields) {
        return $this->clearFields()->addFields(core\collection\Util::flattenArray(func_get_args()));
    }

    public function addFields($fields) {
        $fields = core\collection\Util::flattenArray(func_get_args());
        $sourceManager = $this->getSourceManager();
        $parentSource = $this->_parent->getSource();
        $source = $this->getSource();

        foreach($fields as $fieldName) {
            $field = $sourceManager->realiasOutputField($parentSource, $source, $fieldName);
            $this->_fields[$field->getAlias()] = $field;
        }
        
        return $this;
    }

    public function getFields() {
        return $this->_fields;
    }

    public function removeField($name) {
        unset($this->_fields[$name]);
        return $this;
    }

    public function clearFields() {
        $this->_fields = [];
        return $this;
    }


    public function nullOn($field) {
        $fields = core\collection\Util::flattenArray(func_get_args());

        foreach($fields as $field) {
            if(!isset($this->_fields[$field])) {
                throw new InvalidArgumentException(
                    'Combine field '.$field.' has not been defined'
                );
            }

            $this->_nullFields[$field] = true;
        }

        return $this;
    }

    public function getNullFields() {
        return $this->_nullFields;
    }

    public function removeNullField($field) {
        unset($this->_nullFields[$field]);
        return $this;
    }

    public function clearNullFields() {
        $this->_nullFields = [];
        return $this;
    }


    public function asOne($name) {
        $this->_isCopy = false;
        $this->_parent->addCombine($name, $this);
        return $this->_parent;
    }

    public function asCopy($name) {
        $this->_isCopy = true;
        $this->_parent->addCombine($name, $this);
        return $this->_parent;
    }

    public function isCopy($flag=null) {
        if($flag !== null) {
            $this->_isCopy = (bool)$flag;
            return $this;
        }

        return $this->_isCopy;
    }
}





/*************************
 * Attachments
 */
trait TQuery_AttachBase {

    protected $_attachments = [];

    public function addAttachment($name, IAttachQuery $attachment) {
        $source = $this->getSource();

        if(!$source->getAdapter()->supportsQueryType($attachment->getQueryType())) {
            throw new LogicException(
                'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().
                ' does not support attachments'
            );
        }
        
        if(isset($this->_attachments[$name]) 
        && $this->_attachments[$name] !== $attachment 
        && !($this->_attachments[$name]->isPopulate() && $attachment->isPopulate())) {
            throw new RuntimeException(
                'An attachment has already been created with the name "'.$name.'"'
            );
        }
        
        $this->_attachments[$name] = $attachment;
        return $this;
    }
    
    public function getAttachments() {
        return $this->_attachments;
    }
    
    public function clearAttachments() {
        $this->_attachments = [];
        return $this;
    }
}

trait TQuery_RelationAttachable {

    use TQuery_AttachBase;

    public function attachRelation($relationField) {
        $fields = func_get_args();
        return $this->_attachRelation(array_shift($fields), $fields, !empty($fields) || $this instanceof ISelectQuery);
    }

    public function selectAttachRelation($relationField) {
        $fields = func_get_args();
        return $this->_attachRelation(array_shift($fields), $fields, true);
    }

    public function fetchAttachRelation($relationField) {
        return $this->_attachRelation($relationField, [], false);
    }
    
    private function _attachRelation($relationField, array $fields, $isSelect) {
        $populate = $this->_newQuery()->beginAttachRelation($this, [$relationField], IPopulateQuery::TYPE_ALL, $fields)
            ->isSelect($isSelect);

        $field = $this->_lookupRelationField($relationField, $clusterId);
        $attachment = $field->rewritePopulateQueryToAttachment($populate);

        if(!$attachment instanceof opal\query\IAttachQuery) {
            throw new opal\query\InvalidArgumentException(
                'Cannot populate '.$populate->getFieldName().' - integral schema field cannot convert to attachment'
            );
        }

        return $attachment;
    }
}

trait TQuery_Attachable {
    
    use TQuery_RelationAttachable;

    public function attach() {
        $fields = func_get_args();
        return $this->_newQuery()->beginAttach($this, $fields, !empty($fields) || $this instanceof ISelectQuery);
    }

    public function selectAttach() {
        return $this->_newQuery()->beginAttach($this, func_get_args(), true);
    }

    public function fetchAttach() {
        return $this->_newQuery()->beginAttach($this);
    }
}



trait TQuery_Attachment {
    
    use TQuery_ParentAware;
    use TQuery_NestedComponent;

    protected $_isPopulate = false;
    protected $_type;
    protected $_keyField;
    protected $_valField;
    
    public static function typeIdToName($id) {
        switch($id) {
            case IAttachQuery::TYPE_ONE:
                return 'ONE';
                
            case IAttachQuery::TYPE_MANY:
                return 'MANY';
                
            case IAttachQuery::TYPE_LIST:
                return 'LIST';

            case IAttachQuery::TYPE_VALUE:
                return 'VALUE';
        }
    }

    public static function fromPopulate(IPopulateQuery $populate) {
        $output = new self( 
            $populate->getParentQuery(),
            $populate->getSourceManager(),
            $populate->getSource()
        );

        $output->_isPopulate = true;
        $output->addPrerequisite($populate->getWhereClauseList());
        $output->_order = $populate->getOrderDirectives();
        $output->_limit = $populate->getLimit();
        $output->_offset = $populate->getOffset();

        return $output;
    }
    
    public function isPopulate() {
        return $this->_isPopulate;
    }

    public function getType() {
        return $this->_type;
    }

    public function getParentQuery() {
        return $this->_parent;
    }

    
// Output
    public function asOne($name) {
        if(!$this->_joinClauseList || $this->_joinClauseList->isEmpty()) {
            throw new LogicException(
                'No join clauses have been defined for attachment '.$name
            );
        }
        
        $this->_type = IAttachQuery::TYPE_ONE;

        if($this->_parent instanceof IAttachProviderQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->getNestedParent();
    }
    
    public function asMany($name, $keyField=null) {
        if(!$this->_joinClauseList || $this->_joinClauseList->isEmpty()) {
            throw new LogicException(
                'No join clauses have been defined for attachment '.$name
            );
        }
        
        if($keyField !== null) {
            $this->_keyField = $this->getSourceManager()->extrapolateDataField($this->getSource(), $keyField);
        }
        
        $this->_type = IAttachQuery::TYPE_MANY;

        if($this->_parent instanceof IAttachProviderQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->getNestedParent();
    }
    
    public function getListKeyField() {
        return $this->_keyField;
    }

    public function getListValueField() {
        return $this->_valField;
    }
}


trait TQuery_AttachmentListExtension {
    
    public function asList($name, $field1, $field2=null) {
        if($this->_joinClauseList->isEmpty()) {
            throw new LogicException(
                'No join clauses have been defined for attachment '.$name
            );
        }
        
        $manager = $this->getSourceManager();
        $source = $this->getSource();
        
        if($field2 !== null) {
            $this->_keyField = $manager->extrapolateDataField($source, $field1);
            $this->_valField = $manager->extrapolateDataField($source, $field2);
        } else {
            $this->_valField = $manager->extrapolateDataField($source, $field1);
        }
        
        $this->_type = IAttachQuery::TYPE_LIST;

        if($this->_parent instanceof IAttachProviderQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->getNestedParent();
    }
}


trait TQuery_AttachmentValueExtension {

    public function asValue($name, $field=null) {
        if($field === null) {
            $field = $name;
        }

        if($this->_joinClauseList->isEmpty()) {
            throw new LogicException(
                'No join clauses have been defined for attachment '.$name
            );
        }

        $manager = $this->getSourceManager();
        $source = $this->getSource();

        $this->_valField = $manager->extrapolateDataField($source, $field);
        $this->_type = IAttachQuery::TYPE_VALUE;

        if($this->_parent instanceof IAttachProviderQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->_parent;
    }
}



/***************************
 * Prerequisites
 */
trait TQuery_PrerequisiteClauseFactory {
    
    protected $_prerequisites = [];
    
    public function wherePrerequisite($field, $operator, $value) {
        $source = $this->getSource();
        $source->testWhereClauseSupport();
        
        $this->addPrerequisite(
            opal\query\clause\Clause::factory(
                $this, 
                $this->getSourceManager()->extrapolateIntrinsicField($source, $field), 
                $operator, 
                $value, 
                false
            )
        );
        
        return $this;
    }
    
    public function whereBeginPrerequisite() {
        $this->getSource()->testWhereClauseSupport();
        return new opal\query\clause\WhereList($this, false, true);
    }
    
    public function addPrerequisite(opal\query\IClauseProvider $clause=null) {
        if($clause !== null) {
            $clause->isOr(false);
            $this->_prerequisites[] = $clause;
        }
        
        return $this;
    }
    
    public function getPrerequisites() {
        return $this->_prerequisites;
    }
    
    public function hasPrerequisites() {
        return !empty($this->_prerequisites);
    }
    
    public function clearPrerequisites() {
        $this->_prerequisites = [];
        return $this;
    }
}





/****************************
 * Where clause
 */
trait TQuery_WhereClauseFactory {
    
    protected $_whereClauseList;
    
    public function where($field, $operator, $value) {
        $source = $this->getSource();
        $source->testWhereClauseSupport();
        
        $this->_getWhereClauseList()->addWhereClause(
            opal\query\clause\Clause::factory(
                $this, 
                $this->getSourceManager()->extrapolateIntrinsicField($source, $field), 
                $operator, 
                $value, 
                false
            )
        );
        
        return $this;
    }
    
    public function orWhere($field, $operator, $value) {
        $source = $this->getSource();
        $source->testWhereClauseSupport();
        
        $this->_getWhereClauseList()->addWhereClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($source, $field), 
                $operator, 
                $value, 
                true
            )
        );
        
        return $this;
    }

    public function whereField($leftField, $operator, $rightField) {
        $source = $this->getSource();
        $source->testWhereClauseSupport();
        
        $this->_getWhereClauseList()->addWhereClause(
            opal\query\clause\Clause::factory(
                $this, 
                $this->getSourceManager()->extrapolateIntrinsicField($source, $leftField), 
                $operator, 
                $this->getSourceManager()->extrapolateIntrinsicField($source, $rightField), 
                false
            )
        );
        
        return $this;
    }

    public function orWhereField($leftField, $operator, $rightField) {
        $source = $this->getSource();
        $source->testWhereClauseSupport();
        
        $this->_getWhereClauseList()->addWhereClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($source, $leftField), 
                $operator, 
                $this->getSourceManager()->extrapolateIntrinsicField($source, $rightField), 
                true
            )
        );
    }

    public function whereCorrelation($field, $operator, $keyField) {
        $initiator = $this->_newQuery()->beginCorrelation($this, $keyField);

        $initiator->setApplicator(function($correlation) use ($field, $operator) {
            $this->where($field, $operator, $correlation);
        });

        return $initiator;
    }

    public function orWhereCorrelation($field, $operator, $keyField) {
        $initiator = $this->_newQuery()->beginCorrelation($this, $keyField);

        $initiator->setApplicator(function($correlation) use ($field, $operator) {
            $this->orWhere($field, $operator, $correlation);
        });

        return $initiator;
    }

    public function beginWhereClause() {
        $this->getSource()->testWhereClauseSupport();
        return new opal\query\clause\WhereList($this);
    }
    
    public function beginOrWhereClause() {
        $this->getSource()->testWhereClauseSupport();
        return new opal\query\clause\WhereList($this, true);
    }
    
    
    public function addWhereClause(opal\query\IWhereClauseProvider $clause=null) {
        $this->getSource()->testWhereClauseSupport();
        $this->_getWhereClauseList()->addWhereClause($clause);
        return $this;
    }
    
    public function getWhereClauseList() {
        $output = $this->_getWhereClauseList();

        if($this instanceof IPrerequisiteClauseFactory
        && $this->hasPrerequisites()) {
            $where = $output;
            $output = new opal\query\clause\WhereList($this, false, true);
        
            foreach($this->getPrerequisites() as $clause) {
                $output->_addClause($clause);
            }

            if(!$where->isEmpty()) {
                $output->_addClause($where);
            }
        }

        if($this instanceof ISearchableQuery
        && $this->hasSearch()) {
            $search = $this->getSearch();
            $searchClauses = $search->generateWhereClauseList();

            if($output->isEmpty()) {
                $output = $searchClauses;
            } else {
                $where = $output->isOr(false);
                $output = new opal\query\clause\WhereList($this);
                $output->_addClause($searchClauses);
                $output->_addClause($where);
            }
        }

        return $output;
    }
    
    private function _getWhereClauseList() {
        if(!$this->_whereClauseList) {
            $this->_whereClauseList = new opal\query\clause\WhereList($this);
        }
        
        return $this->_whereClauseList;
    }
    
    public function hasWhereClauses() {
        if(!empty($this->_whereClauseList)) {
            return true;
        }

        if($this instanceof IPrerequisiteClauseFactory 
        && $this->hasPrerequisites()) {
            return true;
        }

        if($this instanceof ISearchableQuery
        && $this->hasSearch()) {
            return true;
        }

        return false;
    }
    
    public function clearWhereClauses() {
        if($this->_whereClauseList) {
            $this->_whereClauseList->clearWhereClauses();
        }
        
        return $this;
    }
}




/**************************
 * Search
 */
trait TQuery_Searchable {

    protected $_searchController;

    public function searchFor($phrase, array $fields=null) {
        $this->_searchController = new SearchController($this, $phrase, $fields);
        return $this;
    }

    public function getSearch() {
        return $this->_searchController;
    }

    public function hasSearch() {
        return $this->_searchController !== null;
    }

    public function clearSearch() {
        $this->_searchController = null;
        return $this;
    }
}





/**************************
 * Groups
 */
trait TQuery_Groupable {
    
    protected $_groups = [];
    
    public function groupBy($field1) {
        $source = $this->getSource();
        $source->testGroupDirectiveSupport();
        
        foreach(func_get_args() as $field) {
            $this->_groups[] = $this->getSourceManager()->extrapolateIntrinsicField($source, $field);
        }
        
        return $this;
    }
    
    public function getGroupFields() {
        return $this->_groups;
    }
    
    public function clearGroupFields() {
        $this->_groups = [];
        return $this;
    }
}





/**************************
 * Having
 */
trait TQuery_HavingClauseFactory {
    
    protected $_havingClauseList;
    
    public function having($field, $operator, $value) {
        $source = $this->getSource();
        $source->testAggregateClauseSupport();
        
        $this->getHavingClauseList()->addHavingClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateAggregateField($source, $field), 
                $operator, 
                $value, 
                false
            )
        );
        
        return $this;
    }
    
    public function orHaving($field, $operator, $value) {
        $source = $this->getSource();
        $source->testAggregateClauseSupport();
        
        $this->getHavingClauseList()->addHavingClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateAggregateField($source, $field), 
                $operator, 
                $value, 
                true
            )
        );
        
        return $this;
    }
    
    public function beginHavingClause() {
        $this->getSource()->testAggregateClauseSupport();
        return new opal\query\clause\HavingList($this);
    }
    
    public function beginOrHavingClause() {
        $this->getSource()->testAggregateClauseSupport();
        return new opal\query\clause\HavingList($this, true);
    }
    
    
    public function addHavingClause(opal\query\IHavingClauseProvider $clause=null) {
        $this->getSource()->testAggregateClauseSupport();
        $this->getHavingClauseList()->addHavingClause($clause);
        return $this;
    }
    
    public function getHavingClauseList() {
        if(!$this->_havingClauseList) {
            $this->_havingClauseList = new opal\query\clause\HavingList($this);
        }
        
        return $this->_havingClauseList;
    }
    
    public function hasHavingClauses() {
        return !empty($this->_havingClauseList) 
            && !$this->_havingClauseList->isEmpty();
    }
    
    public function clearHavingClauses() {
        if($this->_havingClauseList) {
            $this->_havingClauseList->clearHavingClauses();
        }
        
        return $this;
    }
}





/**************************
 * Order
 */
trait TQuery_Orderable {
    
    protected $_order = [];
    
    public function orderBy($field1) {
        $source = $this->getSource();
        $source->testOrderDirectiveSupport();
        
        foreach(func_get_args() as $field) {
            $parts = explode(' ', $field);
            
            $directive = new OrderDirective(
                $this->getSourceManager()->extrapolateField($source, array_shift($parts)), 
                array_shift($parts)
            );
            
            $this->_order[] = $directive;
        }
        
        return $this;
    }
    
    public function setOrderDirectives(array $directives) {
        $this->_order = $directives;
        return $this;
    }
    
    public function getOrderDirectives() {
        return $this->_order;
    }
    
    public function clearOrderDirectives() {
        $this->_order = [];
        return $this;
    }

    public function isPrimaryOrderSource($sourceAlias=null) {
        if(!isset($this->_order[0])) {
            return true;
        }

        if($sourceAlias === null) {
            $sourceAlias = $this->getSource()->getAlias();
        }

        if($sourceAlias instanceof ISource) {
            $sourceAlias = $sourceAlias->getAlias();
        }

        return $this->_order[0]->getField()->getSourceAlias() == $sourceAlias;
    }
}





/*************************
 * Limit
 */
trait TQuery_Limitable {
    
    protected $_limit;
    protected $_maxLimit;
    
    public function limit($limit) {
        $this->getSource()->testLimitDirectiveSupport();
        
        if($limit) {
            $limit = (int)$limit;
            
            if($limit <= 0) {
                $limit = null;
            }
        } else {
            $limit = null;
        }
        
        $this->_limit = $limit;
        
        if($this->_maxLimit !== null && $this->_limit > $this->_maxLimit) {
            $this->_limit = $this->_maxLimit;
        }
        
        return $this;
    }
    
    public function getLimit() {
        return $this->_limit;
    }
    
    public function clearLimit() {
        $this->_limit = null;
        return $this;
    }
    
    public function hasLimit() {
        return $this->_limit !== null;
    }
}





/************************
 * Offset
 */
trait TQuery_Offsettable {
    
    protected $_offset;
    
    public function offset($offset) {
        $this->getSource()->testOffsetDirectiveSupport();
        
        if(!$offset) {
            $offset = null;
        }
        
        $this->_offset = $offset;
        return $this;
    }
    
    public function getOffset() {
        return $this->_offset;
    }
    
    public function clearOffset() {
        $this->_offset = null;
        return $this;
    }
    
    public function hasOffset() {
        return $this->_offset !== null;
    }
}



/*************************
 * Paginator
 */
trait TQuery_Pageable {
    
    protected $_paginator;
    
    public function paginate() {
        if($this->_paginator) {
            return $this->_paginator;
        }
        
        return new Paginator($this);
    }
    
    public function paginateWith($data) {
        return $this->paginate()->applyWith($data);
    }

    public function setPaginator(core\collection\IPaginator $paginator) {
        $this->_paginator = $paginator;
        return $this;
    }
    
    public function getPaginator() {
        /*
        if(!$this->_paginator) {
            $this->_paginator = $this->paginate()
                ->setDefaultLimit($this->_limit)
                ->setDefaultOffset($this->_offset);
        }
        */
        
        return $this->_paginator;
    }
}






/**************************
 * Read
 */
trait TQuery_Read {
    
    protected $_isUnbuffered = false;

    public function isUnbuffered($flag=null) {
        if($flag !== null) {
            $this->_isUnbuffered = (bool)$flag;
            return $this;
        }

        return $this->_isUnbuffered;
    }

    public function getIterator() {
        $data = $this->_fetchSourceData();
        
        if(is_array($data)) {
            $data = new \ArrayIterator($data);
        }
        
        return $data;
    }
    
    public function toArray() {
        return $this->toKeyArray(null);
    }

    public function toKeyArray($keyField) {
        $data = $this->_fetchSourceData($keyField);
        
        if($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }
        
        if(!is_array($data)) {
            throw new UnexpectedValueException(
                'Source did not return a result that could be converted to an array'
            );
        }
        
        return $data;
    }
    
    public function toRow() {
        $limit = $this->_limit;
        $this->_limit = 1;
        $data = $this->toArray();
        $this->_limit = $limit;
        
        return array_shift($data);
    }
    
    public function getRawResult() {
        return $this->_fetchSourceData();
    }
    
    abstract protected function _fetchSourceData($keyField=null, $valField=null);

    public function getOutputManifest() {
        $source = $this->getSource();

        if($source->isDerived()) {
            $output = $source->getAdapter()->getDerivationQuery()->getOutputManifest();
        } else {
            $output = new opal\query\result\OutputManifest($source);
        }

        if($this instanceof IJoinProviderQuery) {
            foreach($this->getJoins() as $join) {
                $output->importSource($join->getSource());
            }
        }

        return $output;
    }

    public function getOutputFields() {
        $output = $this->getSource()->getOutputFields();

        if($this instanceof IJoinProviderQuery) {
            foreach($this->getJoins() as $join) {
                $output = array_merge($output, $join->getSource()->getOutputFields());
            }
        }

        return $output;
    }

    protected function _createBatchIterator($res, opal\query\IField $keyField=null, opal\query\IField $valField=null, $forFetch=false) {
        $output = new opal\query\result\BatchIterator($this->getSource(), $res, $this->getOutputManifest());
        $output->isForFetch($forFetch)
            ->setListKeyField($keyField)
            ->setListValueField($valField);

        if($this instanceof opal\query\IPopulatableQuery) {
            $output->setPopulates($this->getPopulates());
        }

        if($this instanceof opal\query\IAttachProviderQuery) {
            $output->setAttachments($this->getAttachments());
        }

        if($this instanceof opal\query\ICombinableQuery) {
            $output->setCombines($this->getCombines());
        }

        return $output;
    }
}


trait TQuery_SelectSourceDataFetcher {

    protected function _fetchSourceData($keyField=null, $valField=null) {
        if($keyField !== null) {
            $keyField = $this->_sourceManager->extrapolateDataField($this->_source, $keyField);
        }
        
        if($valField !== null) {
            if(isset($this->_attachments[$valField])) {
                $valField = new opal\query\field\Attachment($valField, $this->_attachments[$valField]);
            } else {
                $valField = $this->_sourceManager->extrapolateDataField($this->_source, $valField);
            }
        }

        $parts = explode('\\', get_class($this));
        $func = 'execute'.array_pop($parts).'Query';
        
        $output = $this->_sourceManager->executeQuery($this, function($adapter) use($func) {
            return $adapter->{$func}($this);
        });

        $output = $this->_createBatchIterator($output, $keyField, $valField);

        if($this->_paginator && $this->_offset == 0 && $this->_limit) {
            $count = count($output);

            if($count < $this->_limit) {
                $this->_paginator->setTotal($count);
            }
        }

        return $output;
    }
}






/**************************
 * Insert data
 */
trait TQuery_DataInsert {
    
    protected $_row;
    
    public function setRow($row) {
        if($this instanceof ILocationalQuery && $row instanceof opal\record\ILocationalRecord) {
            $this->inside($row->getQueryLocation());
        }

        if($row instanceof opal\query\IDataRowProvider) {
            $row = $row->toDataRowArray();
        } else if($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        } else if(!is_array($row)) {
            throw new InvalidArgumentException(
                'Insert data must be convertible to an array'
            );
        }
        
        if(empty($row)) {
            throw new InvalidArgumentException(
                'Insert data must contain at least one field'
            );
        }
        
        $this->_row = $row;
        return $this;
    }
    
    public function getRow() {
        return $this->_row;
    }


    protected function _normalizeInsertId($originalId, array $row) {
        $adapter = $this->_source->getAdapter();

        if(!$adapter instanceof IIntegralAdapter) {
            return $originalId;
        }

        $index = $adapter->getQueryAdapterSchema()->getPrimaryIndex();

        if(!$index) {
            return $originalId;
        }

        $fields = $index->getFields();
        $values = [];
        
        foreach($fields as $name => $field) {
            if($originalId 
            && (($field instanceof opal\schema\IAutoIncrementableField && $field->shouldAutoIncrement())
              || $field instanceof opal\schema\IAutoGeneratorField)) {
                $values[$name] = $originalId;
            } else if($field instanceof opal\query\IFieldValueProcessor) {
                $values[$name] = $field->inflateValueFromRow($name, $row, null);
            } else {
                $values[$name] = $originalId;
            }
        }

        return new opal\record\PrimaryKeySet(array_keys($fields), $values);
    }

    protected function _deflateInsertValues(array $row) {
        $adapter = $this->_source->getAdapter();

        if(!$adapter instanceof IIntegralAdapter) {
            return $row;
        }

        $schema = $adapter->getQueryAdapterSchema();
        $values = [];
        
        foreach($schema->getFields() as $name => $field) {
            if($field instanceof opal\schema\INullPrimitiveField) {
                continue;
            }
            
            if(!isset($row[$name])) {
                $value = $field->generateInsertValue($row);
            } else {
                $value = $field->sanitizeValue($row[$name]);
            }

            if($field instanceof opal\schema\IAutoTimestampField 
            && ($value === null || $value === '') 
            && $field->shouldTimestampAsDefault()) {
                continue;
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
}



trait TQuery_BatchDataInsert {
    
    protected $_rows = [];
    protected $_fields = [];
    protected $_dereferencedFields = null;
    protected $_flushThreshold = 500;
    protected $_inserted = 0;
    
    public function addRows($rows) {
        if($rows instanceof core\IArrayProvider) {
            $rows = $rows->toArray();
        } else if(!is_array($rows)) {
            throw new InvalidArgumentException(
                'Batch insert data must be convertible to an array'
            );
        }
        
        foreach($rows as $row) {
            $this->addRow($row);
        }
        
        return $this;
    }
    
    public function addRow($row) {
        $row = $this->_normalizeRow($row);
        
        foreach($row as $field => $value) {
            $this->_fields[$field] = true;
        }
        
        $this->_rows[] = $row;
        
        if($this->_flushThreshold > 0
        && count($this->_rows) >= $this->_flushThreshold) {
            $this->execute();
        }
        
        return $this;
    }
    
    protected function _normalizeRow($row) {
        if($row instanceof opal\query\IDataRowProvider) {
            $row = $row->toDataRowArray();
        } else if($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        } else if(!is_array($row)) {
            throw new InvalidArgumentException(
                'Insert data must be convertible to an array'
            );
        }
        
        if(empty($row)) {
            throw new InvalidArgumentException(
                'Insert data must contain at least one field'
            );
        }
       
        return $row;
    }
    
    
    public function getRows() {
        return $this->_rows;
    }
    
    public function clearRows() {
        $this->_rows = [];
        return $this;
    }
    
    public function getFields() {
        return array_keys($this->_fields);
    }

    public function getDereferencedFields() {
        if($this->_dereferencedFields === null) {
            return $this->getFields();
        }

        return array_keys($this->_dereferencedFields);
    }
    

// Count    
    public function countPending() {
        return count($this->_rows);
    }
    
    public function countInserted() {
        return $this->_inserted;
    }
    
    public function countTotal() {
        return $this->countPending() + $this->countInserted();
    }
    
// Flush threshold
    public function setFlushThreshold($flush) {
        $this->_flushThreshold = (int)$flush;
        return $this;
    }
    
    public function getFlushThreshold() {
        return $this->_flushThreshold;
    }

    protected function _deflateBatchInsertValues(array $rows, array &$queryFields) {
        $adapter = $this->_source->getAdapter();

        if(!$adapter instanceof IIntegralAdapter) {
            return $rows;
        }

        $schema = $adapter->getQueryAdapterSchema();
        $fields = $schema->getFields();
        $queryFields = [];
        $values = [];
        
        foreach($rows as $row) {
            $rowValues = [];
            
            foreach($fields as $name => $field) {
                if($field instanceof opal\schema\INullPrimitiveField) {
                    continue;
                }
                
                if(!isset($row[$name])) {
                    $value = $field->generateInsertValue($row);
                } else {
                    $value = $field->sanitizeValue($row[$name]);
                }

                if($field instanceof opal\schema\IAutoTimestampField 
                && ($value === null || $value === '') 
                && $field->shouldTimestampAsDefault()) {
                    continue;
                }
                
                $value = $field->deflateValue($value);
            
                if(is_array($value)) {
                    foreach($value as $key => $val) {
                        $rowValues[$key] = $val;
                        $queryFields[$key] = true;
                    }
                } else {
                    $rowValues[$name] = $value;
                    $queryFields[$name] = true;
                }
            }
            
            $values[] = $rowValues;
        }

        $queryFields = array_keys($queryFields);
        return $values;
    }
}






/****************************
 * Update data
 */
trait TQuery_DataUpdate {
    
    protected $_valueMap = [];
    
    public function set($key, $value=null) {
        if(is_array($key)) {
            $values = $key;
        } else {
            $values = [$key => $value];
        }
        
        $this->_valueMap = array_merge($this->_valueMap, $values);
    }
    
    public function express($field, $var1) {
        return call_user_func_array([$this, 'beginExpression'], func_get_args())->endExpression();
    }

    public function beginExpression($field, $var1) {
        return new Expression($this, $field, array_slice(func_get_args(), 1));
    }

    public function expressCorrelation($field, $targetField) {
        core\stub($field, $targetField);
    }

    
    public function getValueMap() {
        return $this->_valueMap;
    }

    protected function _deflateUpdateValues(array $values) {
        $adapter = $this->_source->getAdapter();

        if(!$adapter instanceof IIntegralAdapter) {
            return $values;
        }

        $schema = $adapter->getQueryAdapterSchema();
        
        foreach($values as $name => $value) {
            if($value instanceof opal\query\IExpression) {
                continue;
            }

            if(!$field = $schema->getField($name)) {
                continue;
            }
            
            if($field instanceof opal\schema\INullPrimitiveField) {
                unset($values[$name]);
                continue;
            }

            if($field instanceof opal\schema\IAutoTimestampField 
            && ($value === null || $value === '') 
            && !$field->isNullable()) {
                $value = new core\time\Date();
            }
            
            $value = $field->deflateValue($field->sanitizeValue($value));
            
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
}









/**************************
 * Entry point
 */
trait TQuery_EntryPoint {
    
    public function select($field1=null) {
        return Initiator::factory()
            ->beginSelect(func_get_args());
    }

    public function selectDistinct($field1=null) {
        return Initiator::factory()
            ->beginSelect(func_get_args(), true);
    }

    public function union() {
        return Initiator::factory()
            ->beginUnion();
    }

    public function fetch() {
        return Initiator::factory()
            ->beginFetch();
    }
    
    public function insert($row) {
        return Initiator::factory()
            ->beginInsert($row);
    }
    
    public function batchInsert($rows=[]) {
        return Initiator::factory()
            ->beginBatchInsert($rows);
    }
    
    public function replace($row) {
        return Initiator::factory()
            ->beginReplace($row);
    }
    
    public function batchReplace($rows=[]) {
        return Initiator::factory()
            ->beginBatchReplace($rows);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory()
            ->beginUpdate($valueMap);
    }
    
    public function delete() {
        return Initiator::factory()
            ->beginDelete();
    }
    
    public function begin() {
        return new Transaction();
    }
}




/*******************************
 * Implicit source entry point
 */
trait TQuery_ImplicitSourceEntryPoint {
    
    public function select($field1=null) {
        return Initiator::factory()
            ->beginSelect(func_get_args())
            ->from($this);
    }
    
    public function selectDistinct($field1=null) {
        return Initiator::factory()
            ->beginSelect(func_get_args(), true)
            ->from($this);
    }

    public function union() {
        return Initiator::factory()
            ->beginUnion()
            ->with(func_get_args())
            ->from($this);
    }

    public function fetch() {
        return Initiator::factory()
            ->beginFetch()
            ->from($this);
    }
    
    public function insert($row) {
        return Initiator::factory()
            ->beginInsert($row)
            ->into($this);
    }
    
    public function batchInsert($rows=[]) {
        return Initiator::factory()
            ->beginBatchInsert($rows)
            ->into($this);
    }
    
    public function replace($row) {
        return Initiator::factory()
            ->beginReplace($row)
            ->in($this);
    }
    
    public function batchReplace($rows=[]) {
        return Initiator::factory()
            ->beginBatchReplace($rows)
            ->in($this);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory()
            ->beginUpdate($valueMap)
            ->in($this);
    }
    
    public function delete() {
        return Initiator::factory()
            ->beginDelete()
            ->from($this);
    }
    
    public function begin() {
        return new ImplicitSourceTransaction($this);
    }
}