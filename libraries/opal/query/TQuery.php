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
    
    public function getAdapter() {
        return $this->_adapter;
    }
    
    public function getAdapterHash() {
        if($this->_adapterHash === null) {
            $this->_adapterHash = $this->_adapter->getQuerySourceAdapterHash();
        }
        
        return $this->_adapterHash; 
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
    use core\TChainable;
    
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

        return Initiator::factory($sourceManager->getApplication())
            ->setTransaction($sourceManager->getTransaction());
    }


    public function importBlock($name) {
        $adapter = $this->getSource()->getAdapter();

        if(!$adapter instanceof opal\query\IIntegralAdapter) {
            throw new LogicException(
                'Cannot import query block - adapter is not integral'
            );
        }

        $adapter->applyQueryBlock($this, $name, array_slice(func_get_args(), 1));
        return $this;
    }


    public function setTransaction(ITransaction $transaction=null) {
        $this->getSourceManager()->setTransaction($transaction);
        return $this;
    }

    public function getTransaction() {
        return $this->getSourceManager()->getTransaction();
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

        $source = $this->getSource();
        $sourceAdapter = $source->getAdapter();

        if(!$sourceAdapter instanceof IIntegralAdapter) {
            throw new LogicException(
                'Source adapter is not integral and does not have relation meta data'
            );
        }

        $schema = $sourceAdapter->getQueryAdapterSchema();
        $field = $schema->getField($fieldName);

        if(!$field instanceof opal\schema\IManyRelationField) {
            throw new opal\query\InvalidArgumentException(
                'Cannot begin relation correlation - '.$fieldName.' is not a many relation field'
            );
        }

        $application = $this->getSourceManager()->getApplication();
        $fieldAlias = $alias ? $alias : $fieldName;

        if($field instanceof opal\schema\IBridgedRelationField) {
            // Field is bridged
            $bridgeAdapter = $field->getBridgeQueryAdapter($application);
            $bridgeAlias = $fieldAlias.'Bridge';
            $localAlias = $source->getAlias();
            $localName = $field->getBridgeLocalFieldName();
            $targetName = $field->getBridgeTargetFieldName();

            $correlation = $this->correlate($aggregateType.'('.$bridgeAlias.'.'.$targetName.')', $alias)
                ->from($bridgeAdapter, $bridgeAlias)
                ->on($bridgeAlias.'.'.$localName, '=', $localAlias.'.@primary');
        } else {
            // Field is OneToMany (hopefully!)
            $targetAdapter = $field->getTargetQueryAdapter($application);
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
        $output = array();

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
    
    protected $_joins = array();
    
    public function join($field1=null) {
        return $this->_newQuery()->beginJoin($this, func_get_args(), IJoinQuery::INNER);
    }
    
    public function leftJoin($field1=null) {
        return $this->_newQuery()->beginJoin($this, func_get_args(), IJoinQuery::LEFT);
    }
    
    public function rightJoin($field1=null) {
        return $this->_newQuery()->beginJoin($this, func_get_args(), IJoinQuery::RIGHT);
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
        
        $this->_joins = array();
        return $this;
    }
}



trait TQuery_JoinConstrainable {
    
    protected $_joinConstraints = array();
    
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
        
        $this->_joinConstraints = array();
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

        return array();
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
                $sourceManager->extrapolateIntrinsicField($source, $localField),
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
                $sourceManager->extrapolateIntrinsicField($source, $localField),
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
    
    protected $_populates = array();

    public function populate($field1) {
        return $this->_newQuery()->beginPopulate($this, func_get_args(), IPopulateQuery::TYPE_ALL);
    }

    public function populateSelect($field1) {
        return $this->_newQuery()->beginPopulate($this, func_get_args(), IPopulateQuery::TYPE_ALL, true);
    }

    public function populateSome($field) {
        return $this->_newQuery()->beginPopulate($this, [$field], IPopulateQuery::TYPE_SOME);
    }

    public function populateSelectSome($filed) {
        return $this->_newQuery()->beginPopulate($this, [$field], IPopulateQuery::TYPE_SOME, true);
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
        $this->_populates = array();
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
 * Attachments
 */
trait TQuery_Attachable {
    
    protected $_attachments = array();
    
    public function attach() {
        return $this->_newQuery()->beginAttach($this, func_get_args());
    }
    
    public function addAttachment($name, IAttachQuery $attachment) {
        $source = $this->getSource();

        if(!$source->getAdapter()->supportsQueryType($attachment->getQueryType())) {
            throw new LogicException(
                'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().
                ' does not support attachments'
            );
        }
        
        if(isset($this->_attach[$name])) {
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
        $this->_attachments = array();
        return $this;
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

        if($this->_parent instanceof IAttachableQuery) {
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

        if($this->_parent instanceof IAttachableQuery) {
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

        if($this->_parent instanceof IAttachableQuery) {
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

        if($this->_parent instanceof IAttachableQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->_parent;
    }
}



/***************************
 * Prerequisites
 */
trait TQuery_PrerequisiteClauseFactory {
    
    protected $_prerequisites = array();
    
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
        $this->_prerequisites = array();
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
        return $this->_getWhereClauseList();
    }
    
    private function _getWhereClauseList() {
        if(!$this->_whereClauseList) {
            $this->_whereClauseList = new opal\query\clause\WhereList($this);
        }
        
        return $this->_whereClauseList;
    }
    
    public function hasWhereClauses() {
        return !empty($this->_whereClauseList) 
            && !$this->_whereClauseList->isEmpty();
    }
    
    public function clearWhereClauses() {
        if($this->_whereClauseList) {
            $this->_whereClauseList->clearWhereClauses();
        }
        
        return $this;
    }
}


trait TQuery_PrerequisiteAwareWhereClauseFactory {
    
    use TQuery_WhereClauseFactory;
    
    public function getWhereClauseList() {
        $this->_getWhereClauseList();
        
        if(empty($this->_prerequisites)) {
            return $this->_whereClauseList;
        }
        
        $output = new opal\query\clause\WhereList($this, false, true);
        
        foreach($this->_prerequisites as $clause) {
            $output->_addClause($clause);
        }
        
        if(!empty($this->_whereClauseList) && !$this->_whereClauseList->isEmpty()) {
            $output->_addClause($this->_whereClauseList);
        }
        
        return $output;
    }
    
    public function hasWhereClauses() {
        return !empty($this->_prerequisites)
            || (!empty($this->_whereClauseList) && !$this->_whereClauseList->isEmpty());
    }
}






/**************************
 * Groups
 */
trait TQuery_Groupable {
    
    protected $_groups = array();
    
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
        $this->_groups = array();
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
    
    protected $_order = array();
    
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
        $this->_order = array();
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
    
    abstract protected function _fetchSourceData($keyField=null);

    public function getOutputManifest() {
        $output = new opal\query\result\OutputManifest($this->getSource());

        if($this instanceof opal\query\IJoinProviderQuery) {
            foreach($this->getJoins() as $join) {
                $output->importSource($join->getSource());
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

        if($this instanceof opal\query\IAttachableQuery) {
            $output->setAttachments($this->getAttachments());
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
        $values = array();
        
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
        $values = array();
        
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
    
    protected $_rows = array();
    protected $_fields = array();
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
        $this->_rows = array();
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
            return $row;
        }

        $schema = $adapter->getQueryAdapterSchema();
        $fields = $schema->getFields();
        $queryFields = array();
        $values = array();
        
        foreach($rows as $row) {
            $rowValues = array();
            
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
    
    protected $_valueMap = array();
    
    public function set($key, $value=null) {
        if(is_array($key)) {
            $values = $key;
        } else {
            $values = array($key => $value);
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
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginSelect(func_get_args());
    }

    public function selectDistinct($field1=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginSelect(func_get_args(), true);
    }

    public function fetch() {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginFetch();
    }
    
    public function insert($row) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginInsert($row);
    }
    
    public function batchInsert($rows=array()) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginBatchInsert($rows);
    }
    
    public function replace($row) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginReplace($row);
    }
    
    public function batchReplace($rows=array()) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginBatchReplace($rows);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginUpdate($valueMap);
    }
    
    public function delete() {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginDelete();
    }
    
    public function begin() {
        return new Transaction($this->_getEntryPointApplication());
    }
    
    private function _getEntryPointApplication() {
        if($this instanceof core\IApplicationAware) {
            return $this->getApplication();
        } else {
            return df\Launchpad::getActiveApplication();
        }
    }
}




/*******************************
 * Implicit source entry point
 */
trait TQuery_ImplicitSourceEntryPoint {
    
    public function select($field1=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginSelect(func_get_args())
            ->from($this);
    }
    
    public function selectDistinct($field1=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginSelect(func_get_args(), true)
            ->from($this);
    }

    public function fetch() {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginFetch()
            ->from($this);
    }
    
    public function insert($row) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginInsert($row)
            ->into($this);
    }
    
    public function batchInsert($rows=array()) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginBatchInsert($rows)
            ->into($this);
    }
    
    public function replace($row) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginReplace($row)
            ->in($this);
    }
    
    public function batchReplace($rows=array()) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginBatchReplace($rows)
            ->in($this);
    }
    
    public function update(array $valueMap=null) {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginUpdate($valueMap)
            ->in($this);
    }
    
    public function delete() {
        return Initiator::factory($this->_getEntryPointApplication())
            ->beginDelete()
            ->from($this);
    }
    
    public function begin() {
        return new ImplicitSourceTransaction($this->_getEntryPointApplication(), $this);
    }
    
    private function _getEntryPointApplication() {
        if($this instanceof core\IApplicationAware) {
            return $this->getApplication();
        } else {
            return df\Launchpad::getActiveApplication();
        }
    }
}