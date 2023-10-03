<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\query;

use ArrayIterator;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
use df\core;
use df\mesh;

use df\opal;
use df\user;
use Traversable;

trait TQuery_AdapterAware
{
    protected $_adapter;
    private $_adapterHash;
    private $_adapterServerHash;

    public function getAdapter()
    {
        return $this->_adapter;
    }

    public function getAdapterHash()
    {
        if ($this->_adapterHash === null) {
            $this->_adapterHash = $this->_adapter->getQuerySourceAdapterHash();
        }

        return $this->_adapterHash;
    }

    public function getAdapterServerHash()
    {
        if ($this->_adapterServerHash === null) {
            $this->_adapterServerHash = $this->_adapter->getQuerySourceAdapterServerHash();
        }

        return $this->_adapterServerHash;
    }
}


trait TQuery_TransactionAware
{
    protected $_transaction;

    public function setTransaction(mesh\job\ITransaction $transaction = null)
    {
        $this->_transaction = $transaction;
        return $this;
    }

    public function getTransaction(): ?mesh\job\ITransaction
    {
        return $this->_transaction;
    }
}


trait TQuery_ParentAware
{
    protected $_parent;

    public function getParentQuery()
    {
        return $this->_parent;
    }

    public function getParentSourceManager()
    {
        return $this->_parent->getSourceManager();
    }

    public function getParentSource()
    {
        return $this->_parent->getSource();
    }

    public function getParentSourceAlias()
    {
        return $this->_parent->getSourceAlias();
    }

    public function isSourceDeepNested(ISource $source)
    {
        if (!$this->_parent instanceof IParentQueryAware) {
            return false;
        }

        $gp = $this->_parent;
        $sourceId = $source->getId() . ' as ' . $source->getAlias();

        do {
            $gp = $gp->getParentQuery();
            $gpSource = $gp->getSource();

            if ($gpSource->getId() . ' as ' . $gpSource->getAlias() == $sourceId) {
                return true;
            }
        } while ($gp instanceof IParentQueryAware);

        return false;
    }
}


trait TQuery_NestedComponent
{
    protected $_nestedParent;

    public function setNestedParent($parent)
    {
        $this->_nestedParent = $parent;
        return $this;
    }

    public function getNestedParent()
    {
        if ($this->_nestedParent) {
            return $this->_nestedParent;
        }

        if ($this instanceof IParentQueryAware) {
            return $this->getParentQuery();
        }

        return null;
    }
}



/*************************
 * Base
 */
trait TQuery
{
    use user\TAccessLock;
    use core\lang\TChainable;

    public function getAccessLockDomain()
    {
        return $this->getSource()->getAdapter()->getAccessLockDomain();
    }

    public function lookupAccessKey(array $keys, $action = null)
    {
        return $this->getSource()->getAdapter()->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action = null)
    {
        return $this->getSource()->getAdapter()->getDefaultAccess($action);
    }

    public function getAccessSignifiers(): array
    {
        return $this->getSource()->getAdapter()->getAccessSignifiers();
    }

    public function getAccessLockId()
    {
        return $this->getSource()->getAdapter()->getAccessLockId();
    }

    protected function _newQuery()
    {
        $sourceManager = $this->getSourceManager();

        return Initiator::factory()
            ->setTransaction($sourceManager->getTransaction());
    }


    public function importBlock($name, ...$args)
    {
        if (preg_match('/(.+)\.(.+)$/', (string)$name, $matches)) {
            $source = $this->getSourceManager()->getSourceByAlias($matches[1]);

            if (!$source) {
                throw Exceptional::InvalidArgument(
                    'Cannot import query block - adapter source ' . $matches[1] . ' could not be found'
                );
            }

            $name = $matches[2];
        } else {
            $source = $this->getSource();
        }

        $adapter = $source->getAdapter();

        if (!$adapter instanceof IIntegralAdapter) {
            throw Exceptional::Logic(
                'Cannot import query block - adapter is not integral'
            );
        }

        $adapter->applyQueryBlock($this, $name, $args);
        return $this;
    }

    public function importRelationBlock($relationField, $name, ...$args)
    {
        $field = $this->_lookupRelationField($relationField, $queryField);

        if (preg_match('/(.+)\.(.+)$/', (string)$name, $matches)) {
            $source = $this->getSourceManager()->getSourceByAlias($matches[1]);

            if (!$source) {
                throw Exceptional::InvalidArgument(
                    'Cannot import query block - adapter source ' . $matches[1] . ' could not be found'
                );
            }

            $name = $matches[2];
            $adapter = $source->getAdapter();
        } else {
            $adapter = $field->getTargetQueryAdapter();
        }

        if (!$adapter instanceof IIntegralAdapter) {
            throw Exceptional::Logic(
                'Cannot import query block - adapter is not integral'
            );
        }

        $adapter->applyRelationQueryBlock($this, $queryField, $name, $args);
        return $this;
    }


    public function setTransaction(mesh\job\ITransaction $transaction = null)
    {
        $this->getSourceManager()->setTransaction($transaction);
        return $this;
    }

    public function getTransaction(): ?mesh\job\ITransaction
    {
        return $this->getSourceManager()->getTransaction();
    }

    public function getTransactionAdapter()
    {
        return $this->getSource()->getAdapter();
    }

    protected function _lookupRelationField(&$fieldName, &$queryField = null)
    {
        if ($fieldName instanceof IField) {
            $fieldName = $fieldName->getQualifiedName();
        }

        if (preg_match('/(.+) as ([^ ]+)$/', (string)$fieldName, $matches)) {
            $fieldName = $matches[1];
            $sourceAlias = $matches[2];
        } else {
            $sourceAlias = null;
        }

        $source = $this->getSource();
        $field = null;

        if (false === strpos($fieldName, '.')) {
            $sourceAdapter = $source->getAdapter();

            if ($sourceAdapter instanceof IIntegralAdapter) {
                $schema = $sourceAdapter->getQueryAdapterSchema();
                $field = $schema->getField($fieldName);

                if (!$field instanceof opal\schema\IRelationField) {
                    $field = null;
                } else {
                    $queryField = $source->extrapolateIntegralAdapterField($fieldName);
                }
            }
        }

        if (!$field) {
            $sourceManager = $this->getSourceManager();
            $queryField = $sourceManager->extrapolateIntrinsicField($source, $fieldName, true);

            $source = $queryField->getSource();
            $sourceAdapter = $source->getAdapter();

            if (!$sourceAdapter instanceof IIntegralAdapter) {
                throw Exceptional::Logic(
                    'Source adapter is not integral and does not have relation meta data'
                );
            }

            $schema = $sourceAdapter->getQueryAdapterSchema();
            $field = $schema->getField($queryField->getName());

            if (!$field instanceof opal\schema\IRelationField) {
                throw Exceptional::InvalidArgument(
                    $fieldName . ' is not a relation field'
                );
            }
        }

        if ($queryField instanceof IVirtualField) {
            $queryField->setTargetSourceAlias($sourceAlias);
        }

        return $field;
    }
}


trait TQuery_LocalSource
{
    protected $_sourceManager;
    protected $_source;

    public function getSourceManager()
    {
        return $this->_sourceManager;
    }

    public function getSource()
    {
        return $this->_source;
    }

    public function getSourceAlias()
    {
        return $this->_source->getAlias();
    }
}



/****************************
 * Derivable
 */
trait TQuery_Derivable
{
    protected $_derivationParentInitiator;

    public function setDerivationParentInitiator(IInitiator $initiator)
    {
        $this->_derivationParentInitiator = $initiator;
        return $this;
    }

    public function getDerivationParentInitiator()
    {
        return $this->_derivationParentInitiator;
    }

    public function getDerivationSourceAdapter()
    {
        return $this->getSource()->getAdapter();
    }

    public function endSource(string $alias = null): IQuery
    {
        if (!$this->_derivationParentInitiator) {
            throw Exceptional::Logic(
                'Cannot create derived source - no parent initiator has been created'
            );
        }

        $adapter = new DerivedSourceAdapter($this);

        if (!$adapter->supportsQueryType(IQueryTypes::DERIVATION)) {
            throw Exceptional::Logic(
                'Query adapter ' . $adapter->getQuerySourceDisplayName() . ' does not support derived tables'
            );
        }

        if ($alias === null) {
            $alias = $this->getSourceAlias();
        }

        $output = $this->_derivationParentInitiator->from($adapter, $alias);
        $this->_derivationParentInitiator = null;
        return $output;
    }
}




/****************************
 * Locational
 */
trait TQuery_Locational
{
    protected $_location;
    protected $_searchChildLocations = false;

    public function inside($location, $searchChildLocations = false)
    {
        $source = $this->getSource();

        if (!$source->getAdapter()->supportsQueryFeature(IQueryFeatures::LOCATION)) {
            throw Exceptional::Logic(
                'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() .
                ' does not support location base queries'
            );
        }

        $this->_location = $location;
        $this->_searchChildLocations = (bool)$searchChildLocations;
        return $this;
    }

    public function getLocation()
    {
        return $this->_location;
    }

    public function shouldSearchChildLocations(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_searchChildLocations = $flag;
            return $this;
        }

        return $this->_searchChildLocations;
    }
}



/****************************
 * Distinct
 */
trait TQuery_Distinct
{
    protected $_isDistinct = false;

    public function isDistinct(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isDistinct = $flag;
            return $this;
        }

        return $this->_isDistinct;
    }
}



/****************************
 * Correlations
 */

trait TQuery_Correlatable
{
    public function correlate($field, $alias = null)
    {
        return $this->_newQuery()->beginCorrelation($this, $field, $alias);
    }

    public function correlateRelation($relationField, $targetField, $alias = null)
    {
        return $this->_beginRelationCorrelation($relationField, $alias, $targetField)->endCorrelation();
    }

    public function beginCorrelateRelation($relationField, $targetField, $alias = null)
    {
        return $this->_beginRelationCorrelation($relationField, $alias, $targetField);
    }

    public function countRelation($field, $alias = null)
    {
        return $this->_beginRelationCorrelation($field, $alias, 'COUNT')->endCorrelation();
    }

    public function beginCountRelation($field, $alias = null)
    {
        return $this->_beginRelationCorrelation($field, $alias, 'COUNT');
    }

    public function hasRelation($field, $alias = null)
    {
        return $this->_beginRelationCorrelation($field, $alias, 'HAS')->endCorrelation();
    }

    public function beginHasRelation($field, $alias = null)
    {
        return $this->_beginRelationCorrelation($field, $alias, 'HAS');
    }

    protected function _beginRelationCorrelation($fieldName, $alias, $aggregate)
    {
        if ($fieldName instanceof IField) {
            if ($alias === null) {
                $alias = $fieldName->getName();
            }

            $fieldName = $fieldName->getQualifiedName();
        }

        if ($alias === null) {
            $alias = $fieldName;
        }

        $field = $this->_lookupRelationField($fieldName, $queryField);

        if (!$field instanceof opal\schema\IManyRelationField) {
            throw Exceptional::InvalidArgument(
                'Cannot begin relation correlation - ' . $fieldName . ' is not a many relation field'
            );
        }

        $source = $queryField->getSource();
        $fieldAlias = $alias ?? $fieldName;

        if ($field instanceof opal\schema\IBridgedRelationField) {
            // Field is bridged
            $bridgeAdapter = $field->getBridgeQueryAdapter();
            $bridgeAlias = $fieldAlias . 'Bridge';
            $localAlias = $source->getAlias();
            $localName = $field->getBridgeLocalFieldName();
            $targetName = $field->getBridgeTargetFieldName();

            if (false === strpos($aggregate, '(')) {
                $aggregate .= '(' . $bridgeAlias . '.' . $targetName . ')';
            }

            // TODO: check if field is on target table!!!

            $correlation = $this->correlate($aggregate, $alias)
                ->from($bridgeAdapter, $bridgeAlias)
                ->on($bridgeAlias . '.' . $localName, '=', $localAlias . '.@primary');
        } elseif ($field instanceof opal\schema\IInverseRelationField) {
            // Field is OneToMany (hopefully!)
            $targetAdapter = $field->getTargetQueryAdapter();
            $targetAlias = $fieldAlias;
            $targetFieldName = $field->getTargetField();
            $localAlias = $source->getAlias();

            if (false === strpos($aggregate, '(')) {
                $aggregate .= '(' . $targetAlias . '.@primary)';
            }

            $correlation = $this->correlate($aggregate, $alias)
                ->from($targetAdapter, $targetAlias)
                ->on($targetAlias . '.' . $targetFieldName, '=', $localAlias . '.@primary');
        } else {
            throw Exceptional::Runtime(
                'Unsupport ManyRelation field type',
                null,
                $field
            );
        }

        $correlation->endCorrelation();
        return $correlation;
    }

    public function addCorrelation(ICorrelationQuery $correlation)
    {
        $source = $this->getSource();
        $adapter = $source->getAdapter();

        if (!$adapter->supportsQueryType($correlation->getQueryType())) {
            throw Exceptional::Logic(
                'Query adapter ' . $adapter->getQuerySourceDisplayName() . ' does not support correlations'
            );
        }

        $field = new opal\query\field\Correlation($correlation);
        $source->addOutputField($field);
        $paginator = $this->getPaginator();

        if ($paginator instanceof opal\query\IPaginator) {
            $paginator->addOrderableFields($field->getAlias());
        }

        return $this;
    }

    public function getCorrelations()
    {
        $source = $this->getSource();
        $output = [];

        foreach ($source->getOutputFields() as $name => $field) {
            if ($field instanceof ICorrelationField) {
                $output[$name] = $field;
            }
        }

        return $output;
    }
}



/****************************
 * Joins
 */
trait TQuery_JoinProvider
{
    protected $_joins = [];

    public function getJoins()
    {
        return $this->_joins;
    }

    public function clearJoins()
    {
        $sourceManager = $this->getSourceManager();

        foreach ($this->_joins as $sourceAlias => $join) {
            $sourceManager->removeSource($sourceAlias);
        }

        $this->_joins = [];
        return $this;
    }

    protected function _beginJoinRelation($fieldName, array $targetFields = null, $joinType = IJoinQuery::INNER)
    {
        $targetAlias = null;

        if ($fieldName instanceof IField) {
            if ($fieldName instanceof IVirtualField) {
                $targetAlias = $fieldName->getTargetSourceAlias();
            }

            $fieldName = $fieldName->getQualifiedName();
        }

        $field = $this->_lookupRelationField($fieldName, $queryField);

        if ($targetFields === null) {
            $join = $this->_newQuery()->beginJoinConstraint($this, $joinType);
        } else {
            $join = $this->_newQuery()->beginJoin($this, $targetFields, $joinType);
        }

        if (!$targetAlias && $queryField instanceof IVirtualField) {
            $targetAlias = $queryField->getTargetSourceAlias();
        }

        if (!$targetAlias) {
            $targetAlias = $field->getName();
        }

        if ($field instanceof opal\schema\IBridgedRelationField) {
            // Field is bridged
            Glitch::incomplete($field);
            /*
            $bridgeAdapter = $field->getBridgeQueryAdapter();
            $bridgeAlias = $fieldName.'Bridge';
            $localAlias = $source->getAlias();
            $localName = $field->getBridgeLocalFieldName();
            $targetName = $field->getBridgeTargetFieldName();

            $correlation = $this->correlate($aggregateType.'('.$bridgeAlias.'.'.$targetName.')', $alias)
                ->from($bridgeAdapter, $bridgeAlias)
                ->on($bridgeAlias.'.'.$localName, '=', $localAlias.'.@primary');
             */
        } elseif (
            $field instanceof opal\schema\IInverseRelationField &&
            (
                $field instanceof opal\schema\IManyRelationField ||
                (
                    $field instanceof opal\schema\IOneRelationField &&
                    $field instanceof opal\schema\INullPrimitiveField
                )
            )
        ) {
            // Field is OneToMany || inverse One
            $targetAdapter = $field->getTargetQueryAdapter();
            $targetFieldName = $field->getTargetField();

            $join = $join->from($targetAdapter, $targetAlias)
                ->on($targetAlias . '.' . $targetFieldName, '=', '@primary');
        } else {
            // Field is One
            $targetAdapter = $field->getTargetQueryAdapter();

            $join = $join->from($targetAdapter, $targetAlias)
                ->on($targetAlias . '.@primary', '=', $fieldName);
        }

        return $join;
    }
}


trait TQuery_Joinable
{
    use TQuery_JoinProvider;

    // Inner
    public function join(...$fields)
    {
        return $this->_newQuery()->beginJoin($this, $fields, IJoinQuery::INNER);
    }

    public function joinRelation($relationField, ...$fields)
    {
        return $this->_beginJoinRelation($relationField, $fields, IJoinQuery::INNER)->endJoin();
    }

    public function beginJoinRelation($relationField, ...$fields)
    {
        return $this->_beginJoinRelation($relationField, $fields, IJoinQuery::INNER);
    }


    // Left
    public function leftJoin(...$fields)
    {
        return $this->_newQuery()->beginJoin($this, $fields, IJoinQuery::LEFT);
    }

    public function leftJoinRelation($relationField, ...$fields)
    {
        return $this->_beginJoinRelation($relationField, $fields, IJoinQuery::LEFT)->endJoin();
    }

    public function beginLeftJoinRelation($relationField, ...$fields)
    {
        return $this->_beginJoinRelation($relationField, $fields, IJoinQuery::LEFT);
    }


    // Right
    public function rightJoin(...$fields)
    {
        return $this->_newQuery()->beginJoin($this, $fields, IJoinQuery::RIGHT);
    }

    public function rightJoinRelation($relationField, ...$fields)
    {
        return $this->_beginJoinRelation($relationField, $fields, IJoinQuery::RIGHT)->endJoin();
    }

    public function beginRightJoinRelation($relationField, ...$fields)
    {
        return $this->_beginJoinRelation($relationField, $fields, IJoinQuery::RIGHT);
    }



    public function addJoin(IJoinQuery $join)
    {
        $join->isConstraint(false);
        $source = $this->getSource();

        if (!$source->getAdapter()->supportsQueryType($join->getQueryType())) {
            throw Exceptional::Logic(
                'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' does not support joins'
            );
        }

        $this->_joins[$join->getSourceAlias()] = $join;
        return $this;
    }
}



trait TQuery_JoinConstrainable
{
    use TQuery_JoinProvider;

    public function joinConstraint()
    {
        return $this->_newQuery()->beginJoinConstraint($this, IJoinQuery::INNER);
    }

    public function joinRelationConstraint($relationField)
    {
        return $this->_beginJoinRelation($relationField, null, IJoinQuery::INNER)->endJoin();
    }

    public function beginJoinRelationConstraint($relationField)
    {
        return $this->_beginJoinRelation($relationField, null, IJoinQuery::INNER);
    }

    public function leftJoinConstraint()
    {
        return $this->_newQuery()->beginJoinConstraint($this, IJoinQuery::LEFT);
    }

    public function leftJoinRelationConstraint($relationField)
    {
        return $this->_beginJoinRelation($relationField, null, IJoinQuery::LEFT)->endJoin();
    }

    public function beginLeftJoinRelationConstraint($relationField)
    {
        return $this->_beginJoinRelation($relationField, null, IJoinQuery::LEFT);
    }

    public function rightJoinConstraint()
    {
        return $this->_newQuery()->beginJoinConstraint($this, IJoinQuery::RIGHT);
    }

    public function rightJoinRelationConstraint($relationField)
    {
        return $this->_beginJoinRelation($relationField, null, IJoinQuery::RIGHT)->endJoin();
    }

    public function beginRightJoinRelationConstraint($relationField)
    {
        return $this->_beginJoinRelation($relationField, null, IJoinQuery::RIGHT);
    }

    public function addJoinConstraint(IJoinQuery $join)
    {
        $join->isConstraint(true);
        $source = $this->getSource();

        if (!$source->getAdapter()->supportsQueryType($join->getQueryType())) {
            throw Exceptional::Logic(
                'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' does not support joins'
            );
        }

        $this->_joins[$join->getSourceAlias()] = $join;
        return $this;
    }
}



trait TQuery_JoinClauseFactoryBase
{
    protected $_joinClauseList;

    public function beginOnClause()
    {
        return new opal\query\clause\JoinList($this);
    }

    public function beginOrOnClause()
    {
        return new opal\query\clause\JoinList($this, true);
    }


    public function addJoinClause(IJoinClauseProvider $clause = null)
    {
        $this->getJoinClauseList()->addJoinClause($clause);
        return $this;
    }

    public function getJoinClauseList()
    {
        if (!$this->_joinClauseList) {
            $this->_joinClauseList = new opal\query\clause\JoinList($this);
        }

        return $this->_joinClauseList;
    }

    public function hasJoinClauses()
    {
        return !empty($this->_joinClauseList)
            && !$this->_joinClauseList->isEmpty();
    }

    public function clearJoinClauses()
    {
        if ($this->_joinClauseList) {
            $this->_joinClauseList->clearJoinClauses();
        }

        return $this;
    }

    public function getNonLocalFieldReferences()
    {
        if ($this->_joinClauseList) {
            return $this->_joinClauseList->getNonLocalFieldReferences();
        }

        return [];
    }

    public function referencesSourceAliases(array $sourceAliases)
    {
        if ($this->_joinClauseList) {
            return $this->_joinClauseList->referencesSourceAliases($sourceAliases);
        }

        return false;
    }
}

trait TQuery_ParentAwareJoinClauseFactory
{
    use TQuery_JoinClauseFactoryBase;

    public function on($localField, $operator, $foreignField)
    {
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

    public function orOn($localField, $operator, $foreignField)
    {
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
trait TQuery_Populatable
{
    protected $_populates = [];

    public function populate(...$fields)
    {
        return $this->_newQuery()->beginPopulate($this, $fields, IPopulateQuery::TYPE_ALL)->endPopulate();
    }

    public function populateSelect($populateField, ...$fields)
    {
        return $this->_newQuery()->beginPopulate($this, [$populateField], IPopulateQuery::TYPE_ALL, $fields)
            ->isSelect(true)
            ->endPopulate();
    }

    public function populateSome($field)
    {
        return $this->_newQuery()->beginPopulate($this, [$field], IPopulateQuery::TYPE_SOME);
    }

    public function populateSelectSome($populateField, ...$fields)
    {
        return $this->_newQuery()->beginPopulate($this, [$populateField], IPopulateQuery::TYPE_SOME, $fields)
            ->isSelect(true);
    }

    public function addPopulate(IPopulateQuery $populate)
    {
        $source = $this->getSource();

        if (empty($this->_populates)) {
            $source->addOutputField(
                $this->getSourceManager()->extrapolateIntrinsicField(
                    $source,
                    '@primary'
                )
            );
        }

        $this->_populates[$populate->getFieldName()] = $populate;
        $source->addOutputField($populate->getField());

        return $this;
    }

    public function getPopulate($fieldName)
    {
        if ($fieldName instanceof IField) {
            $fieldName = $fieldName->getName();
        }

        if (isset($this->_populates[$fieldName])) {
            return $this->_populates[$fieldName];
        }
    }

    public function getPopulates()
    {
        return $this->_populates;
    }

    public function clearPopulates()
    {
        $this->_populates = [];
        return $this;
    }
}


trait TQuery_Populate
{
    use TQuery_ParentAware;
    use TQuery_NestedComponent;

    protected $_field;
    protected $_type;
    protected $_isSelect = false;

    public function getQueryType()
    {
        return IQueryTypes::POPULATE;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function getFieldName()
    {
        return $this->_field->getName();
    }

    public function getPopulateType()
    {
        return $this->_type;
    }

    public function isSelect(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isSelect = $flag;
            return $this;
        }

        return $this->_isSelect;
    }

    public function endPopulate()
    {
        $this->_parent->addPopulate($this);
        return $this->getNestedParent();
    }
}





/*************************
 * Combine
 */
trait TQuery_Combinable
{
    protected $_combines = [];

    public function combine(...$fields)
    {
        return $this->_newQuery()->beginCombine($this, $fields);
    }

    public function addCombine($name, ICombineQuery $combine)
    {
        if ($name instanceof IField) {
            $name = $name->getName();
        }

        $this->_combines[$name] = $combine;
        return $this;
    }

    public function getCombine(string $name): ?ICombineQuery
    {
        return $this->_combines[$name] ?? null;
    }

    public function getCombines()
    {
        return $this->_combines;
    }

    public function clearCombines()
    {
        $this->_combines = [];
        return $this;
    }
}


trait TQuery_Combine
{
    use TQuery_ParentAware;
    use TQuery_NestedComponent;

    protected $_fields = [];
    protected $_nullFields = [];
    protected $_isCopy = false;

    public function getQueryType()
    {
        return IQueryTypes::COMBINE;
    }

    public function setFields(...$fields)
    {
        return $this->clearFields()->addFields(...$fields);
    }

    public function addFields(...$fields)
    {
        $sourceManager = $this->getSourceManager();
        $parentSource = $this->_parent->getSource();
        $source = $this->getSource();

        foreach (core\collection\Util::leaves($fields) as $fieldName) {
            $field = $sourceManager->realiasOutputField($parentSource, $source, $fieldName);
            $this->_fields[$field->getAlias()] = $field;
        }

        return $this;
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function removeField($name)
    {
        if ($name instanceof IField) {
            $name = $name->getName();
        }

        unset($this->_fields[$name]);
        return $this;
    }

    public function clearFields()
    {
        $this->_fields = [];
        return $this;
    }


    public function nullOn(...$fields)
    {
        foreach (core\collection\Util::leaves($fields) as $field) {
            if (!isset($this->_fields[$field])) {
                throw Exceptional::InvalidArgument(
                    'Combine field ' . $field . ' has not been defined'
                );
            }

            $this->_nullFields[$field] = true;
        }

        return $this;
    }

    public function getNullFields()
    {
        return $this->_nullFields;
    }

    public function removeNullField($field)
    {
        unset($this->_nullFields[$field]);
        return $this;
    }

    public function clearNullFields()
    {
        $this->_nullFields = [];
        return $this;
    }


    public function asOne($name)
    {
        $this->_isCopy = false;
        $this->_parent->addCombine($name, $this);
        return $this->_parent;
    }

    public function asCopy($name)
    {
        $this->_isCopy = true;
        $this->_parent->addCombine($name, $this);
        return $this->_parent;
    }

    public function isCopy(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isCopy = $flag;
            return $this;
        }

        return $this->_isCopy;
    }
}





/*************************
 * Attachments
 */
trait TQuery_AttachBase
{
    protected $_attachments = [];

    public function addAttachment($name, IAttachQuery $attachment)
    {
        if ($name instanceof IField) {
            $name = $name->getName();
        }

        $source = $this->getSource();

        if (!$source->getAdapter()->supportsQueryType($attachment->getQueryType())) {
            throw Exceptional::Logic(
                'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() .
                ' does not support attachments'
            );
        }

        if (isset($this->_attachments[$name])
        && $this->_attachments[$name] !== $attachment
        && !($this->_attachments[$name]->isPopulate() && $attachment->isPopulate())) {
            throw Exceptional::Runtime(
                'An attachment has already been created with the name "' . $name . '"'
            );
        }

        $this->_attachments[$name] = $attachment;
        return $this;
    }

    public function getAttachments()
    {
        return $this->_attachments;
    }

    public function clearAttachments()
    {
        $this->_attachments = [];
        return $this;
    }
}

trait TQuery_RelationAttachable
{
    use TQuery_AttachBase;

    public function attachRelation($relationField, ...$fields)
    {
        return $this->_attachRelation($relationField, $fields, !empty($fields) || $this instanceof ISelectQuery);
    }

    public function selectAttachRelation($relationField, ...$fields)
    {
        return $this->_attachRelation($relationField, $fields, true);
    }

    public function fetchAttachRelation($relationField)
    {
        return $this->_attachRelation($relationField, [], false);
    }

    private function _attachRelation($relationField, array $fields, $isSelect)
    {
        $populate = $this->_newQuery()->beginAttachRelation($this, [$relationField], IPopulateQuery::TYPE_ALL, $fields)
            ->isSelect($isSelect);

        $field = $this->_lookupRelationField($relationField);
        $attachment = $field->rewritePopulateQueryToAttachment($populate);

        if (!$attachment instanceof IAttachQuery) {
            throw Exceptional::InvalidArgument(
                'Cannot populate ' . $populate->getFieldName() . ' - integral schema field cannot convert to attachment'
            );
        }

        return $attachment;
    }
}

trait TQuery_Attachable
{
    use TQuery_RelationAttachable;

    public function attach(...$fields)
    {
        return $this->_newQuery()->beginAttach($this, $fields, !empty($fields) || $this instanceof ISelectQuery);
    }

    public function selectAttach(...$fields)
    {
        return $this->_newQuery()->beginAttach($this, $fields, true);
    }

    public function fetchAttach()
    {
        return $this->_newQuery()->beginAttach($this);
    }
}



trait TQuery_Attachment
{
    use TQuery_ParentAware;
    use TQuery_NestedComponent;

    protected $_isPopulate = false;
    protected $_type;
    protected $_keyField;
    protected $_valField;

    public static function typeIdToName($id)
    {
        switch ($id) {
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

    public static function fromPopulate(IPopulateQuery $populate)
    {
        $output = new self(
            $parent = $populate->getParentQuery(),
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

    public function isPopulate()
    {
        return $this->_isPopulate;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getParentQuery()
    {
        return $this->_parent;
    }


    // Output
    public function asOne($name)
    {
        if ($name instanceof IField) {
            $name = $name->getName();
        }

        if (!$this->_joinClauseList || $this->_joinClauseList->isEmpty()) {
            throw Exceptional::Logic(
                'No join clauses have been defined for attachment ' . $name
            );
        }

        $this->_type = IAttachQuery::TYPE_ONE;

        if ($this->_parent instanceof IAttachProviderQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->getNestedParent();
    }

    public function asMany($name, $keyField = null)
    {
        if ($name instanceof IField) {
            $name = $name->getName();
        }

        if (!$this->_joinClauseList || $this->_joinClauseList->isEmpty()) {
            throw Exceptional::Logic(
                'No join clauses have been defined for attachment ' . $name
            );
        }

        if ($keyField !== null) {
            $this->_keyField = $this->getSourceManager()->extrapolateDataField($this->getSource(), $keyField);
        }

        $this->_type = IAttachQuery::TYPE_MANY;

        if ($this->_parent instanceof IAttachProviderQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->getNestedParent();
    }

    public function getListKeyField()
    {
        return $this->_keyField;
    }

    public function getListValueField()
    {
        return $this->_valField;
    }
}


trait TQuery_AttachmentListExtension
{
    public function asList($name, $field1, $field2 = null)
    {
        if ($this->_joinClauseList->isEmpty()) {
            throw Exceptional::Logic(
                'No join clauses have been defined for attachment ' . $name
            );
        }

        $manager = $this->getSourceManager();
        $source = $this->getSource();

        if ($field2 !== null) {
            $this->_keyField = $manager->extrapolateDataField($source, $field1);
            $this->_valField = $manager->extrapolateDataField($source, $field2);
        } else {
            $this->_valField = $manager->extrapolateDataField($source, $field1);
        }

        $this->_type = IAttachQuery::TYPE_LIST;

        if ($this->_parent instanceof IAttachProviderQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->getNestedParent();
    }
}


trait TQuery_AttachmentValueExtension
{
    public function asValue($name, $field = null)
    {
        if ($field === null) {
            $field = $name;
        }

        if ($this->_joinClauseList->isEmpty()) {
            throw Exceptional::Logic(
                'No join clauses have been defined for attachment ' . $name
            );
        }

        $manager = $this->getSourceManager();
        $source = $this->getSource();

        $this->_valField = $manager->extrapolateDataField($source, $field);
        $this->_type = IAttachQuery::TYPE_VALUE;

        if ($this->_parent instanceof IAttachProviderQuery) {
            $this->_parent->addAttachment($name, $this);
        }

        return $this->_parent;
    }
}



/***************************
 * Prerequisites
 */
trait TQuery_PrerequisiteClauseFactory
{
    protected $_prerequisites = [];

    public function wherePrerequisite($field, $operator, $value)
    {
        $source = $this->getSource();

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

    public function whereBeginPrerequisite()
    {
        return new opal\query\clause\WhereList($this, false, true);
    }

    public function addPrerequisite(IClauseProvider $clause = null)
    {
        if ($clause !== null) {
            $clause->isOr(false);
            $this->_prerequisites[] = $clause;
        }

        return $this;
    }

    public function getPrerequisites()
    {
        return $this->_prerequisites;
    }

    public function hasPrerequisites()
    {
        return !empty($this->_prerequisites);
    }

    public function clearPrerequisites()
    {
        $this->_prerequisites = [];
        return $this;
    }
}





/****************************
 * Where clause
 */
trait TQuery_WhereClauseFactory
{
    protected $_whereClauseList;

    public function where($field, $operator, $value)
    {
        $source = $this->getSource();

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

    public function orWhere($field, $operator, $value)
    {
        $source = $this->getSource();

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

    public function whereField($leftField, $operator, $rightField)
    {
        $source = $this->getSource();

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

    public function orWhereField($leftField, $operator, $rightField)
    {
        $source = $this->getSource();

        $this->_getWhereClauseList()->addWhereClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($source, $leftField),
                $operator,
                $this->getSourceManager()->extrapolateIntrinsicField($source, $rightField),
                true
            )
        );

        return $this;
    }

    public function whereCorrelation($field, $operator, $keyField)
    {
        $initiator = $this->_newQuery()->beginCorrelation($this, $keyField);

        $initiator->setApplicator(function ($correlation) use ($field, $operator) {
            $this->where($field, $operator, $correlation);
        });

        return $initiator;
    }

    public function orWhereCorrelation($field, $operator, $keyField)
    {
        $initiator = $this->_newQuery()->beginCorrelation($this, $keyField);

        $initiator->setApplicator(function ($correlation) use ($field, $operator) {
            $this->orWhere($field, $operator, $correlation);
        });

        return $initiator;
    }

    public function beginWhereClause()
    {
        return new opal\query\clause\WhereList($this);
    }

    public function beginOrWhereClause()
    {
        return new opal\query\clause\WhereList($this, true);
    }


    public function addWhereClause(IWhereClauseProvider $clause = null)
    {
        $this->_getWhereClauseList()->addWhereClause($clause);
        return $this;
    }

    public function getWhereClauseList()
    {
        $output = $this->_getWhereClauseList();

        if ($this instanceof IPrerequisiteClauseFactory
        && $this->hasPrerequisites()) {
            $where = $output;
            $output = new opal\query\clause\WhereList($this, false, true);

            foreach ($this->getPrerequisites() as $clause) {
                $output->_addClause($clause);
            }

            if (!$where->isEmpty()) {
                $output->_addClause($where);
            }
        }

        if ($this instanceof ISearchableQuery
        && $this->hasSearch()) {
            $search = $this->getSearch();
            $searchClauses = $search->generateWhereClauseList();

            if ($output->isEmpty()) {
                $output = $searchClauses;
            } else {
                $where = $output->isOr(false);
                $output = new opal\query\clause\WhereList($this);
                $output->_addClause($searchClauses->isOr(false));
                $output->_addClause($where);
            }
        }

        return $output;
    }

    private function _getWhereClauseList()
    {
        if (!$this->_whereClauseList) {
            $this->_whereClauseList = new opal\query\clause\WhereList($this);
        }

        return $this->_whereClauseList;
    }

    public function hasWhereClauses()
    {
        if (!empty($this->_whereClauseList)) {
            return true;
        }

        if ($this instanceof IPrerequisiteClauseFactory
        && $this->hasPrerequisites()) {
            return true;
        }

        if ($this instanceof ISearchableQuery
        && $this->hasSearch()) {
            return true;
        }

        return false;
    }

    public function clearWhereClauses()
    {
        if ($this->_whereClauseList) {
            $this->_whereClauseList->clearWhereClauses();
        }

        return $this;
    }
}




/**************************
 * Search
 */
trait TQuery_Searchable
{
    protected $_searchController;

    public function searchFor(?string $phrase, array $fields = null)
    {
        if ($phrase === null) {
            return $this;
        }

        $this->_searchController = new SearchController($this, $phrase, $fields);
        return $this;
    }

    public function getSearch()
    {
        return $this->_searchController;
    }

    public function hasSearch()
    {
        return $this->_searchController !== null;
    }

    public function clearSearch()
    {
        $this->_searchController = null;
        return $this;
    }
}





/**************************
 * Groups
 */
trait TQuery_Groupable
{
    protected $_groups = [];

    public function groupBy(...$fields)
    {
        $source = $this->getSource();

        foreach ($fields as $field) {
            $this->_groups[] = $this->getSourceManager()->extrapolateIntrinsicField($source, $field);
        }

        return $this;
    }

    public function getGroupFields()
    {
        return $this->_groups;
    }

    public function clearGroupFields()
    {
        $this->_groups = [];
        return $this;
    }
}





/**************************
 * Having
 */
trait TQuery_HavingClauseFactory
{
    protected $_havingClauseList;

    public function having($field, $operator, $value)
    {
        $source = $this->getSource();

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

    public function orHaving($field, $operator, $value)
    {
        $source = $this->getSource();

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

    public function havingField($leftField, $operator, $rightField)
    {
        $source = $this->getSource();

        $this->getHavingClauseList()->addHavingClause(
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

    public function orHavingField($leftField, $operator, $rightField)
    {
        $source = $this->getSource();

        $this->getHavingClauseList()->addHavingClause(
            opal\query\clause\Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($source, $leftField),
                $operator,
                $this->getSourceManager()->extrapolateIntrinsicField($source, $rightField),
                true
            )
        );

        return $this;
    }

    public function beginHavingClause()
    {
        return new opal\query\clause\HavingList($this);
    }

    public function beginOrHavingClause()
    {
        return new opal\query\clause\HavingList($this, true);
    }


    public function addHavingClause(IHavingClauseProvider $clause = null)
    {
        $this->getHavingClauseList()->addHavingClause($clause);
        return $this;
    }

    public function getHavingClauseList()
    {
        if (!$this->_havingClauseList) {
            $this->_havingClauseList = new opal\query\clause\HavingList($this);
        }

        return $this->_havingClauseList;
    }

    public function hasHavingClauses()
    {
        return !empty($this->_havingClauseList)
            && !$this->_havingClauseList->isEmpty();
    }

    public function clearHavingClauses()
    {
        if ($this->_havingClauseList) {
            $this->_havingClauseList->clearHavingClauses();
        }

        return $this;
    }
}





/**************************
 * Order
 */
trait TQuery_Orderable
{
    protected $_order = [];

    public function orderBy(...$fields)
    {
        $source = $this->getSource();

        foreach ($fields as $field) {
            if ($field instanceof IField) {
                $field = $field->getQualifiedName();
            }

            $parts = explode(' ', $field);

            $directive = new OrderDirective(
                $this->getSourceManager()->extrapolateField($source, array_shift($parts)),
                array_shift($parts)
            );

            $this->_order[] = $directive;
        }

        return $this;
    }

    public function setOrderDirectives(array $directives)
    {
        $this->_order = $directives;
        return $this;
    }

    public function getOrderDirectives()
    {
        return $this->_order;
    }

    public function hasOrderDirectives()
    {
        return !empty($this->_order);
    }

    public function clearOrderDirectives()
    {
        $this->_order = [];
        return $this;
    }

    public function isPrimaryOrderSource($sourceAlias = null)
    {
        if (!isset($this->_order[0])) {
            return true;
        }

        if ($sourceAlias === null) {
            $sourceAlias = $this->getSource()->getAlias();
        }

        if ($sourceAlias instanceof ISource) {
            $sourceAlias = $sourceAlias->getAlias();
        }

        return $this->_order[0]->getField()->getSourceAlias() == $sourceAlias;
    }
}





/**************************
 * Order
 */
trait TQuery_Nestable
{
    protected $_nest = [];

    public function nestOn(...$fields)
    {
        $source = $this->getSource();

        foreach ($fields as $field) {
            if ($field instanceof IField) {
                $field = $field->getQualifiedName();
            }

            $this->_nest[] = $this->getSourceManager()->extrapolateField($source, $field);
        }

        return $this;
    }

    public function setNestFields(array $directives)
    {
        $this->_nest = $directives;
        return $this;
    }

    public function getNestFields()
    {
        return $this->_nest;
    }

    public function hasNestFields()
    {
        return !empty($this->_nest);
    }

    public function clearNestFields()
    {
        $this->_nest = [];
        return $this;
    }
}





/*************************
 * Limit
 */
trait TQuery_Limitable
{
    protected $_limit;
    protected $_maxLimit;

    public function limit($limit)
    {
        if ($limit) {
            $limit = (int)$limit;

            if ($limit <= 0) {
                $limit = null;
            }
        } else {
            $limit = null;
        }

        $this->_limit = $limit;

        if ($this->_maxLimit !== null && $this->_limit > $this->_maxLimit) {
            $this->_limit = $this->_maxLimit;
        }

        return $this;
    }

    public function getLimit()
    {
        return $this->_limit;
    }

    public function clearLimit()
    {
        $this->_limit = null;
        return $this;
    }

    public function hasLimit()
    {
        return $this->_limit !== null;
    }
}





/************************
 * Offset
 */
trait TQuery_Offsettable
{
    protected $_offset;

    public function offset($offset)
    {
        if (!$offset) {
            $offset = null;
        }

        $this->_offset = $offset;
        return $this;
    }

    public function getOffset()
    {
        return $this->_offset;
    }

    public function clearOffset()
    {
        $this->_offset = null;
        return $this;
    }

    public function hasOffset()
    {
        return $this->_offset !== null;
    }
}



/*************************
 * Paginator
 */
trait TQuery_Pageable
{
    protected $_paginator;

    public function paginate()
    {
        if (!$this->_paginator) {
            $this->_paginator = new Paginator($this);
        }

        return $this->_paginator;
    }

    public function paginateWith($data)
    {
        return $this->paginate()->applyWith($data);
    }

    public function setPaginator(?core\collection\IPaginator $paginator)
    {
        $this->_paginator = $paginator;
        return $this;
    }

    public function getPaginator(): ?core\collection\IPaginator
    {
        return $this->_paginator;
    }
}






/**************************
 * Read
 */
trait TQuery_Read
{
    protected $_isUnbuffered = false;

    public function isUnbuffered(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isUnbuffered = $flag;
            return $this;
        }

        return $this->_isUnbuffered;
    }

    public function getIterator(): Traversable
    {
        $data = $this->_fetchSourceData();

        if (is_array($data)) {
            $data = new ArrayIterator($data);
        }

        return $data;
    }

    public function __invoke()
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return $this->toKeyArray(null);
    }

    public function toKeyArray($keyField)
    {
        $data = $this->_fetchSourceData($keyField);

        if ($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }

        if (!is_array($data)) {
            throw Exceptional::UnexpectedValue(
                'Source did not return a result that could be converted to an array'
            );
        }

        return $data;
    }

    public function toRow()
    {
        $limit = $this->_limit;
        $this->_limit = 1;
        $data = $this->toArray();
        $this->_limit = $limit;

        return array_shift($data);
    }

    public function getRawResult()
    {
        return $this->_fetchSourceData();
    }

    abstract protected function _fetchSourceData($keyField = null, $valField = null);

    public function getOutputManifest()
    {
        $source = $this->getSource();

        if ($source->isDerived()) {
            $output = $source->getAdapter()->getDerivationQuery()->getOutputManifest();
        } else {
            $output = new opal\query\OutputManifest($source);
        }

        if ($this instanceof IJoinProviderQuery) {
            foreach ($this->getJoins() as $join) {
                $output->importSource($join->getSource());
            }
        }

        return $output;
    }

    public function getOutputFields()
    {
        $output = $this->getSource()->getOutputFields();

        if ($this instanceof IJoinProviderQuery) {
            foreach ($this->getJoins() as $join) {
                $output = array_merge($output, $join->getSource()->getOutputFields());
            }
        }

        return $output;
    }

    protected function _createBatchIterator($res, IField $keyField = null, IField $valField = null, $forFetch = false, callable $formatter = null)
    {
        $output = new BatchIterator($this->getSource(), $res, $this->getOutputManifest());

        $output->isForFetch($forFetch)
            ->setListKeyField($keyField)
            ->setListValueField($valField)
            ->setFormatter($formatter);

        if ($this instanceof IPopulatableQuery) {
            $output->setPopulates($this->getPopulates());
        }

        if ($this instanceof IAttachProviderQuery) {
            $output->setAttachments($this->getAttachments());
        }

        if ($this instanceof ICombinableQuery) {
            $output->setCombines($this->getCombines());
        }

        if ($this instanceof INestableQuery) {
            $output->setNestFields(...$this->getNestFields());
        }

        return $output;
    }
}


trait TQuery_SelectSourceDataFetcher
{
    protected function _fetchSourceData($keyField = null, $valField = null)
    {
        $source = $this->getSource();

        if ($keyField !== null) {
            $keyField = $this->_sourceManager->extrapolateDataField($source, $keyField);
        }

        $formatter = null;

        if (is_string($valField)) {
            if (isset($this->_attachments[$valField])) {
                $valField = new opal\query\field\Attachment($valField, $this->_attachments[$valField]);
            } else {
                $valField = $this->_sourceManager->extrapolateDataField($source, $valField);
            }

            $source->addOutputField($valField);
        } elseif (is_callable($valField)) {
            $formatter = $valField;
            $valField = null;
        }

        $source->setKeyField($keyField);

        $parts = explode('\\', get_class($this));
        $func = 'execute' . array_pop($parts) . 'Query';

        $output = $this->_sourceManager->executeQuery($this, function ($adapter) use ($func) {
            return $adapter->{$func}($this);
        });

        $output = $this->_createBatchIterator($output, $keyField, $valField, false, $formatter);

        if ($this->_paginator && $this->_offset == 0 && $this->_limit) {
            $count = count($output);

            if ($count < $this->_limit) {
                $this->_paginator->setTotal($count);
            }
        }

        //$source->setKeyField(null);

        return $output;
    }
}






/**************************
 * Write
 */
trait TQuery_Write
{
    public function __invoke()
    {
        return $this->execute();
    }
}


/**************************
 * Insert data
 */
trait TQuery_DataInsert
{
    protected $_shouldReplace = false;
    protected $_ifNotExists = false;

    public function shouldReplace(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldReplace = $flag;
            return $this;
        }

        return $this->_shouldReplace;
    }

    public function ifNotExists(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_ifNotExists = $flag;
            return $this;
        }

        return $this->_ifNotExists;
    }
}



/**************************
 * Entry point
 */
trait TQuery_EntryPoint
{
    public function select(...$fields)
    {
        $output = Initiator::factory()
            ->beginSelect($fields);

        if ($this instanceof IAdapter) {
            $output = $output->from($this);
        }

        return $output;
    }

    public function selectDistinct(...$fields)
    {
        $output = Initiator::factory()
            ->beginSelect($fields, true);

        if ($this instanceof IAdapter) {
            $output = $output->from($this);
        }

        return $output;
    }

    public function countAll()
    {
        if (!$this instanceof IAdapter) {
            throw Exceptional::Runtime(
                'Cannot count all without implicit source'
            );
        }

        /** @var ISelectQuery $query */
        $query = $this->select();
        return $query->count();
    }

    public function countAllDistinct()
    {
        if (!$this instanceof IAdapter) {
            throw Exceptional::Runtime(
                'Cannot count all without implicit source'
            );
        }

        return $this->selectDistinct()->count();
    }

    public function union(...$fields)
    {
        $output = Initiator::factory()
            ->beginUnion()
            ->with($fields);

        if ($this instanceof IAdapter) {
            $output = $output->from($this);
        }

        return $output;
    }

    public function fetch()
    {
        $output = Initiator::factory()
            ->beginFetch();

        if ($this instanceof IAdapter) {
            $output = $output->from($this);
        }

        return $output;
    }

    public function insert($row)
    {
        $output = Initiator::factory()
            ->beginInsert($row);

        if ($this instanceof IAdapter) {
            $output = $output->into($this);
        }

        return $output;
    }

    public function batchInsert($rows = [])
    {
        $output = Initiator::factory()
            ->beginBatchInsert($rows);

        if ($this instanceof IAdapter) {
            $output = $output->into($this);
        }

        return $output;
    }

    public function replace($row)
    {
        $output = Initiator::factory()
            ->beginReplace($row);

        if ($this instanceof IAdapter) {
            $output = $output->in($this);
        }

        return $output;
    }

    public function batchReplace($rows = [])
    {
        $output = Initiator::factory()
            ->beginBatchReplace($rows);

        if ($this instanceof IAdapter) {
            $output = $output->in($this);
        }

        return $output;
    }

    public function update(array $valueMap = null)
    {
        $output = Initiator::factory()
            ->beginUpdate($valueMap);

        if ($this instanceof IAdapter) {
            $output = $output->in($this);
        }

        return $output;
    }

    public function delete()
    {
        $output = Initiator::factory()
            ->beginDelete();

        if ($this instanceof IAdapter) {
            $output = $output->from($this);
        }

        return $output;
    }

    public function newTransaction(): mesh\job\ITransaction
    {
        if ($this instanceof IAdapter) {
            return new Transaction($this);
        } else {
            return new Transaction();
        }
    }
}
