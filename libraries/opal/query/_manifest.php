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
use df\mesh;

// Exceptions
interface IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}

class OperatorException extends InvalidArgumentException {}
class ValueException extends InvalidArgumentException {}


// Interfaces
interface IDataRowProvider {
    public function toDataRowArray();
}



// Initiator
interface IInitiator extends mesh\job\ITransactionAware {
    public function beginSelect(array $fields=[], $distinct=false);
    public function beginUnion();
    public function beginUnionSelect(IUnionQuery $union, array $fields=[], $unionDistinct=true, $selectDistinct=false);
    public function beginFetch();
    public function beginInsert($row);
    public function beginBatchInsert($rows=[]);
    public function beginReplace($row);
    public function beginBatchReplace($rows=[]);
    public function beginUpdate(array $valueMap=null);
    public function beginDelete();

    public function beginCorrelation(ISourceProvider $parent, $field, $alias=null);
    public function beginPopulate(IQuery $parent, array $fields, $type=IPopulateQuery::TYPE_ALL, array $selectFields=null);
    public function beginCombine(ICombinableQuery $parent, array $fields);

    public function beginJoin(IQuery $parent, array $fields=[], $type=IJoinQuery::INNER);
    public function beginJoinConstraint(IQuery $parent, $type=IJoinQuery::INNER);
    public function beginAttach(IReadQuery $parent, array $fields=[], $isSelect=false);
    public static function beginAttachFromPopulate(IPopulateQuery $populate);

    public function getFields();
    public function getFieldMap();
    public function getData();
    public function getParentQuery();
    public function getJoinType();

    public function setApplicator(Callable $applicator=null);
    public function getApplicator();

    public function from($sourceAdapter, $alias=null);
    public function fromUnion();
    public function fromSelect(...$fields);
    public function fromSelectDistinct(...$fields);
    public function into($sourceAdapter, $alias=null);
    public function in($sourceAdapter, $alias=null);
}




// Source provider
interface ISourceProvider {
    public function getSourceManager();
    public function getSource();
    public function getSourceAlias();
}

interface IParentSourceProvider {
    public function getParentSourceManager();
    public function getParentSource();
    public function getParentSourceAlias();
}

interface IParentQueryAware extends IParentSourceProvider {
    public function getParentQuery();
    public function isSourceDeepNested(ISource $source);
}

interface INestedComponent {
    public function setNestedParent($parent);
    public function getNestedParent();
}


// Entry point
interface IEntryPoint extends mesh\job\ITransactionInitiator {
    public function select(...$fields);
    public function selectDistinct(...$fields);
    public function countAll();
    public function countAllDistinct();
    public function union(...$fields);
    public function fetch();
    public function insert($values);
    public function batchInsert($rows=[]);
    public function replace($values);
    public function batchReplace($rows=[]);
    public function update(array $valueMap=null);
    public function delete();
}



// Clause factory
interface IClauseFactory {}

interface IJoinClauseFactory extends IClauseFactory {
    public function on($localField, $operator, $foreignField);
    public function orOn($localField, $operator, $foreignField);
    public function beginOnClause();
    public function beginOrOnClause();

    public function addJoinClause(IJoinClauseProvider $clause=null);
    public function getJoinClauseList();
    public function hasJoinClauses();
    public function clearJoinClauses();
}

interface IPrerequisiteClauseFactory extends IClauseFactory {
    public function wherePrerequisite($field, $operator, $value);
    public function whereBeginPrerequisite();
    public function addPrerequisite(IClauseProvider $clause=null);
    public function getPrerequisites();
    public function hasPrerequisites();
    public function clearPrerequisites();
}

interface IWhereClauseFactory extends IClauseFactory {
    public function where($field, $operator, $value);
    public function orWhere($field, $operator, $value);
    public function whereField($leftField, $operator, $rightField);
    public function orWhereField($leftField, $operator, $rightField);
    public function whereCorrelation($field, $operator, $keyField);
    public function orWhereCorrelation($field, $operator, $keyField);
    public function beginWhereClause();
    public function beginOrWhereClause();

    public function addWhereClause(IWhereClauseProvider $clause=null);
    public function getWhereClauseList();
    public function hasWhereClauses();
    public function clearWhereClauses();
}


interface IHavingClauseFactory extends IClauseFactory {
    public function having($field, $operator, $value);
    public function orHaving($field, $operator, $value);
    public function beginHavingClause();
    public function beginOrHavingClause();

    public function addHavingClause(IHavingClauseProvider $clause=null);
    public function getHavingClauseList();
    public function hasHavingClauses();
    public function clearHavingClauses();
}



// Query
interface IQuery extends
    ISourceProvider,
    mesh\job\ITransactionAdapterProvider,
    mesh\job\ITransactionAware,
    user\IAccessLock,
    core\lang\IChainable {
    public function getQueryType();
    public function importBlock($name, ...$args);
    public function importRelationBlock($relationField, $name, ...$args);
}

interface IReadQuery extends IQuery, \IteratorAggregate, core\IArrayProvider {
    public function __invoke();
    public function toKeyArray($keyField);
    public function toRow();
    public function getRawResult();
    public function getOutputManifest();
    public function getOutputFields();
    public function isUnbuffered(bool $flag=null);
}

interface IWriteQuery extends IQuery {
    public function __invoke();
    public function execute();
}

interface IDistinctQuery extends IQuery {
    public function isDistinct(bool $flag=null);
}

interface IDerivableQuery extends IQuery {
    public function setDerivationParentInitiator(IInitiator $initiator);
    public function getDerivationParentInitiator();
    public function getDerivationSourceAdapter();
}

interface ICorrelatableQuery extends IQuery {
    public function correlate($field, $alias=null);
    public function correlateRelation($relationField, $targetField, $alias=null);
    public function beginCorrelateRelation($relationField, $targetField, $alias=null);
    public function countRelation($field, $alias=null);
    public function beginCountRelation($field, $alias=null);
    public function hasRelation($field, $alias=null);
    public function beginHasRelation($field, $alias=null);
    public function addCorrelation(ICorrelationQuery $correlation);
    public function getCorrelations();
}

interface IJoinProviderQuery extends IQuery {
    public function getJoins();
    public function clearJoins();
}

interface IJoinableQuery extends IJoinProviderQuery {
    public function join(...$fields);
    public function joinRelation($relationField, ...$fields);
    public function beginJoinRelation($relationField, ...$fields);
    public function leftJoin(...$fields);
    public function leftJoinRelation($relationField, ...$fields);
    public function beginLeftJoinRelation($relationField, ...$fields);
    public function rightJoin(...$fields);
    public function rightJoinRelation($relationField, ...$fields);
    public function beginRightJoinRelation($relationField, ...$fields);
    public function addJoin(IJoinQuery $join);
}

interface IJoinConstrainableQuery extends IJoinProviderQuery {
    public function joinConstraint();
    public function joinRelationConstraint($relationField);
    public function beginJoinRelationConstraint($relationField);
    public function leftJoinConstraint();
    public function leftJoinRelationConstraint($relationField);
    public function beginLeftJoinRelationConstraint($relationField);
    public function rightJoinConstraint();
    public function rightJoinRelationConstraint($relationField);
    public function beginRightJoinRelationConstraint($relationField);
    public function addJoinConstraint(IJoinQuery $join);
}

interface IAttachProviderQuery extends IReadQuery {
    public function addAttachment($name, IAttachQuery $attachment);
    public function getAttachments();
    public function clearAttachments();
}

interface IRelationAttachableQuery extends IAttachProviderQuery {
    public function attachRelation($relationField, ...$fields);
    public function selectAttachRelation($relationField, ...$fields);
    public function fetchAttachRelation($relationField);
}

interface IAttachableQuery extends IRelationAttachableQuery {
    public function attach(...$fields);
    public function selectAttach(...$fields);
    public function fetchAttach();
}

interface IPopulatableQuery extends IQuery {
    public function populate(...$fields);
    public function populateSelect($populateField, ...$fields);
    public function populateSome($field);
    public function populateSelectSome($populateField, ...$fields);
    public function addPopulate(IPopulateQuery $populate);
    public function getPopulate($fieldName);
    public function getPopulates();
    public function clearPopulates();
}

interface ICombinableQuery extends IQuery {
    public function combine(...$fields);
    public function addCombine($name, ICombineQuery $combine);
    public function getCombines();
    public function clearCombines();
}


interface IWhereClauseQuery extends IQuery, IWhereClauseFactory {}
interface IPrerequisiteClauseQuery extends IWhereClauseQuery, IPrerequisiteClauseFactory {}
interface IHavingClauseQuery extends IReadQuery, IHavingClauseFactory {}

interface ISearchableQuery extends IReadQuery {
    public function searchFor($phrase, array $fields=null);
    public function getSearch();
    public function hasSearch();
    public function clearSearch();
}

interface IGroupableQuery extends IReadQuery {
    public function groupBy(...$fields);
    public function getGroupFields();
    public function clearGroupFields();
}

interface IOrderableQuery extends IQuery {
    public function orderBy(...$fields);
    public function setOrderDirectives(array $directives);
    public function getOrderDirectives();
    public function hasOrderDirectives();
    public function clearOrderDirectives();
    public function isPrimaryOrderSource($sourceAlias=null);
}

interface INestableQuery extends IQuery {
    public function nestOn(...$fields);
    public function setNestFields(array $fields);
    public function getNestFields();
    public function hasNestFields();
    public function clearNestFields();
}

interface ILimitableQuery extends IQuery {
    public function limit($limit);
    public function getLimit();
    public function clearLimit();
    public function hasLimit();
}

interface IOffsettableQuery extends IQuery {
    public function offset($offset);
    public function getOffset();
    public function clearOffset();
    public function hasOffset();
}

interface ILocationalQuery extends IQuery {
    public function inside($location, $searchChildLocations=false);
    public function getLocation();
    public function shouldSearchChildLocations(bool $flag=null);
}


interface ICorrelationQuery extends
    IQuery,
    IParentQueryAware,
    INestedComponent,
    IJoinConstrainableQuery,
    IJoinClauseFactory,
    IWhereClauseFactory,
    ILimitableQuery,
    IOffsettableQuery {
    public function setApplicator(Callable $applicator=null);
    public function getApplicator();
    public function getFieldAlias();
    public function endCorrelation($fieldAlias=null);

    public function getCorrelationSource();
    public function getCorrelatedClauses(ISource $correlationSource=null);
}


interface IJoinQuery extends
    IQuery,
    IParentQueryAware,
    INestedComponent,
    IJoinClauseFactory,
    IWhereClauseFactory {

    const INNER = 0;
    const LEFT = 1;
    const RIGHT = 2;

    public function addOutputFields(string ...$fields);
    public function getType();
    public function isConstraint(bool $flag=null);
    public function endJoin();
    public function referencesSourceAliases(array $sourceAliases);
}


interface IPopulateQuery extends
    IQuery,
    IParentQueryAware,
    INestedComponent,
    IPopulatableQuery,
    IWhereClauseQuery,
    IOrderableQuery,
    ILimitableQuery,
    IOffsettableQuery
    {

    const TYPE_ALL = 1;
    const TYPE_SOME = 2;

    public function getField();
    public function getFieldName();
    public function isSelect(bool $flag=null);
    public function endPopulate();
}


interface ICombineQuery extends
    IQuery,
    IParentQueryAware,
    INestedComponent {

    public function setFields(...$fields);
    public function addFields(...$fields);
    public function getFields();
    public function removeField($name);
    public function clearFields();

    public function nullOn(...$fields);
    public function getNullFields();
    public function removeNullField($field);
    public function clearNullFields();

    public function asOne($name);
    public function asCopy($name);
    public function isCopy(bool $flag=null);
}


interface IAttachQuery extends
    IQuery,
    IParentQueryAware,
    INestedComponent,
    IJoinClauseFactory {

    const TYPE_ONE = 0;
    const TYPE_MANY = 1;
    const TYPE_LIST = 2;
    const TYPE_VALUE = 3;

    public function isPopulate();
    public function getType();
    public function asOne($name);
    public function asMany($name, $keyField=null);
    public function getNonLocalFieldReferences();
}


interface IOrderDirective extends core\IStringProvider {
    public function setField(IField $field);
    public function getField();
    public function isFieldNullable();
    public function setDirection($direction);
    public function getDirection();
    public function getReversedDirection();
    public function isDescending(bool $flag=null);
    public function isAscending(bool $flag=null);
    public function setNullOrder($order);
    public function getNullOrder();
}

class NullOrder extends core\lang\Enum {
    const FIRST = 'first';
    const LAST = 'last';
    const ASCENDING = 'ascending';
    const DESCENDING = 'descending';
}


interface IPageableQuery extends IReadQuery, core\collection\IPageable {
    public function paginate();
    public function paginateWith($data);
}



/***********
 * Select
 */
interface ISelectQuery extends
    IReadQuery,
    \Countable,
    IDerivableQuery,
    ILocationalQuery,
    IDistinctQuery,
    ICorrelatableQuery,
    IJoinableQuery,
    IAttachableQuery,
    IPopulatableQuery,
    ICombinableQuery,
    IPrerequisiteClauseQuery,
    ISearchableQuery,
    IGroupableQuery,
    IHavingClauseQuery,
    IOrderableQuery,
    INestableQuery,
    ILimitableQuery,
    IOffsettableQuery,
    IPageableQuery {
    public function addOutputFields(string ...$fields);
    public function toList($field1, $field2=null);
    public function toValue($valField=null);
}

interface ISelectAttachQuery extends ISelectQuery, IAttachQuery {
    public function asList($name, $field1, $field2=null);
    public function asValue($name, $field=null);
    public function getListKeyField();
    public function getListValueField();
}


/***********
 * Union
 */
interface IUnionQuery extends
    IReadQuery,
    \Countable,
    IDerivableQuery,
    IAttachableQuery,
    ICombinableQuery,
    IOrderableQuery,
    INestableQuery,
    ILimitableQuery,
    IOffsettableQuery,
    IPageableQuery {
    public function with(...$fields);
    public function withAll(...$fields);
    public function addQuery(IUnionSelectQuery $query);
    public function getQueries();
}

interface IUnionSelectQuery extends ISelectQuery {
    public function isUnionDistinct(bool $flag=null);

    public function endSelect();
    public function with(...$fields);
    public function withAll(...$fields);
}


/***********
 * Fetch
 */
interface IFetchQuery extends
    IReadQuery,
    \Countable,
    ILocationalQuery,
    ICorrelatableQuery,
    IJoinConstrainableQuery,
    IRelationAttachableQuery,
    IPopulatableQuery,
    IPrerequisiteClauseQuery,
    ISearchableQuery,
    IOrderableQuery,
    INestableQuery,
    ILimitableQuery,
    IOffsettableQuery,
    IPageableQuery {}

interface IFetchAttachQuery extends IFetchQuery, IAttachQuery {
    public function getListKeyField();
}


/***********
 * Insert
 */
interface IDataInsertQuery extends IWriteQuery, ILocationalQuery {
    public function shouldReplace(bool $flag=null);
    public function ifNotExists(bool $flag=null);
}


interface IInsertQuery extends IDataInsertQuery {
    public function setRow($row);
    public function getRow();
    public function getPreparedRow();
}

interface IBatchInsertQuery extends IDataInsertQuery {
    public function addRows($rows);
    public function addRow($row);
    public function getRows();
    public function getPreparedRows();
    public function clearRows();
    public function getFields();
    public function getDereferencedFields();

    public function countPending();
    public function countInserted();
    public function countTotal();

    public function setFlushThreshold($flush);
    public function getFlushThreshold();
}



/***********
 * Update
 */
interface IDataUpdateQuery extends IWriteQuery, ILocationalQuery {
    public function set($field, $value=null);
    public function express($field, ...$elements);
    public function beginExpression($field, ...$elements);
    public function expressCorrelation($field, $targetField);
    public function getValues();
    public function getPreparedValues();
}

interface IUpdateQuery extends
    IDataUpdateQuery,
    IPrerequisiteClauseQuery,
    IOrderableQuery,
    ILimitableQuery {}


/***********
 * Delete
 */
interface IDeleteQuery extends
    IWriteQuery,
    ILocationalQuery,
    IPrerequisiteClauseQuery,
    ILimitableQuery {}



/***********
 * Source
 */
interface IQueryTypes {
    const SELECT = 1;
    const UNION = 2;
    const FETCH = 3;
    const INSERT = 4;
    const BATCH_INSERT = 5;
    const REPLACE = 6;
    const BATCH_REPLACE = 7;
    const UPDATE = 8;
    const DELETE = 9;

    const CORRELATION = 101;
    const DERIVATION = 102;

    const POPULATE = 151;
    const COMBINE = 152;

    const JOIN = 201;
    const JOIN_CONSTRAINT = 202;
    const REMOTE_JOIN = 211;

    const SELECT_ATTACH = 301;
    const FETCH_ATTACH = 302;
    const REMOTE_ATTACH = 311;
}

interface IQueryFeatures {
    const AGGREGATE = 1;
    const WHERE_CLAUSE = 2;
    const GROUP_DIRECTIVE = 3;
    const HAVING_CLAUSE = 4;
    const ORDER_DIRECTIVE = 5;
    const LIMIT = 6;
    const OFFSET = 7;
    const TRANSACTION = 10;
    const VALUE_PROCESSOR = 101;
    const LOCATION = 201;
}


interface IAdapterAware {
    public function getAdapter();
    public function getAdapterHash();
    public function getAdapterServerHash();
}


interface IAdapter extends user\IAccessLock, mesh\job\IJobAdapter {
    public function getQuerySourceId();
    public function getQuerySourceAdapterHash();
    public function getQuerySourceAdapterServerHash();
    public function getQuerySourceDisplayName();
    public function getDelegateQueryAdapter();

    public function supportsQueryType($type);
    public function supportsQueryFeature($feature);

    public function handleQueryException(IQuery $query, \Exception $e);
    public function ensureStorageConsistency();

    public function executeSelectQuery(ISelectQuery $query);
    public function countSelectQuery(ISelectQuery $query);
    public function executeUnionQuery(IUnionQuery $query);
    public function countUnionQuery(IUnionQuery $query);
    public function executeFetchQuery(IFetchQuery $query);
    public function countFetchQuery(IFetchQuery $query);
    public function executeInsertQuery(IInsertQuery $query);
    public function executeBatchInsertQuery(IBatchInsertQuery $query);
    public function executeUpdateQuery(IUpdateQuery $query);
    public function executeDeleteQuery(IDeleteQuery $query);

    public function fetchRemoteJoinData(IJoinQuery $join, array $rows);
    public function fetchAttachmentData(IAttachQuery $attachment, array $rows);

    public function newRecord(array $values=null);
    public function newPartial(array $values=null);
    public function shouldRecordsBroadcastHookEvents();
}

interface IIntegralAdapter extends IAdapter {
    public function getQueryAdapterSchema();

    public function prepareQueryClauseValue(IField $field, $value);
    public function rewriteVirtualQueryClause(IClauseFactory $parent, IVirtualField $field, $operator, $value, $isOr=false);
    public function getDefaultSearchFields();

    public function getQueryResultValueProcessors(array $fields=null);
    public function applyQueryBlock(IQuery $query, $name, array $args);
    public function applyRelationQueryBlock(IQuery $query, opal\query\IField $relationField, $name, array $args);

}

interface INaiveIntegralAdapter extends IAdapter {
    public function getPrimaryIndex();
}

interface IPaginatingAdapter extends IAdapter {
    public function applyPagination(IPaginator $paginator);
}

interface IDerivedSourceAdapter extends IAdapter {
    public function getDerivationQuery();
    public function getDerivationSource();
}


// Source
interface ISource extends IAdapterAware {
    public function getAlias();
    public function getId();
    public function getUniqueId();
    public function getHash();
    public function getDisplayName();
    public function isDerived();
    public function isPrimary(bool $flag=null);

    public function handleQueryException(IQuery $query, \Exception $e);

    public function extrapolateIntegralAdapterField($name, $alias=null, opal\schema\IField $field=null);
    public function extrapolateIntegralAdapterFieldFromSchemaField($name, $alias, opal\schema\IField $field=null);

    public function getFieldProcessor(IIntrinsicField $field);

    public function addOutputField(IField $field);
    public function addPrivateField(IField $field);
    public function getFieldByAlias($alias);
    public function getFieldByQualifiedName($qName);
    public function getFirstOutputDataField();
    public function getLastOutputDataField();
    public function isOutputField(IField $field);
    public function hasWildcardField();
    public function getWildcardField();
    public function removeWildcardOutputField($name);

    public function setKeyField(IField $field=null);
    public function getKeyField();

    public function getOutputFields();
    public function getDereferencedOutputFields();
    public function getPrivateFields();
    public function getDereferencedPrivateFields();
    public function getAllFields();
    public function getAllDereferencedFields();
}


// Manager
interface ISourceManager extends mesh\job\ITransactionAware {
    public function getMeshManager();

    public function setParentSourceManager(ISourceManager $parent);
    public function getParentSourceManager();

    public function newSource($adapter, $alias, array $fields=null, $forWrite=false);
    public function removeSource($alias);
    public function getSources();
    public function getSourceByAlias($alias);
    public function countSourceAdapters();
    public function canQueryLocally();

    public function executeQuery(IQuery $query, Callable $executor);

    public function extrapolateSourceAdapter($adapter);
    public function extrapolateOutputField(ISource $source, $name);
    public function realiasOutputField(ISource $parentSource, ISource $source, $name);
    public function extrapolateField(ISource $source, $name);
    public function extrapolateIntrinsicField(ISource $source, $name, $checkAlias=null);
    public function extrapolateAggregateField(ISource $source, $name, $checkAlias=null);
    public function extrapolateDataField(ISource $source, $name, $checkAlias=null);
    public function generateAlias();
}



// Transaction
interface ITransaction extends mesh\job\ITransaction, IEntryPoint {}




// Fields
interface IField extends core\IStringProvider {
    public function getSource();
    public function getSourceAlias();

    public function getName();
    public function getAlias();
    public function setAlias($alias);
    public function hasDiscreetAlias();
    public function getQualifiedName();
    public function dereference();
    public function isOutputField();

    public function setLogicalAlias($alias);
    public function getLogicalAlias();

    public function setOverrideField(IField $field=null);
    public function getOverrideField();

    public function shouldBeProcessed();
    public function rewriteAsDerived(ISource $source);

    public function isFromWildcard(bool $flag=null);
}

trait TField {

    use core\TStringProvider;

    protected $_logicalAlias;
    protected $_overrideField;
    protected $_isFromWildcard = false;

    public function setLogicalAlias($alias) {
        if(empty($alias)) {
            $alias = null;
        }

        $this->_logicalAlias = $alias;
        return $this;
    }

    public function getLogicalAlias() {
        return $this->_logicalAlias;
    }

    public function setOverrideField(IField $field=null) {
        $this->_overrideField = $field;
        return $this;
    }

    public function getOverrideField() {
        return $this->_overrideField;
    }

    public function shouldBeProcessed() {
        return true;
    }

    public function isFromWildcard(bool $flag=null) {
        if($flag !== null) {
            $this->_isFromWildcard = $flag;
            return $this;
        }

        return $this->_isFromWildcard;
    }

    public function getDumpProperties() {
        return $this->toString();
    }
}

interface IIntrinsicField extends IField {}

interface IWildcardField extends IField {
    public function addMuteField($name, $alias=null);
    public function removeMuteField($name);
    public function getMuteFields();
}

interface ICorrelationField extends IField {
    public function getCorrelationQuery();
    public function getAggregateOutputField();
}

interface IAggregateField extends IField {
    public function getType();
    public function getTypeName();
    public function getTargetField();
    public function normalizeOutputValue($value);
    public function isDistinct(bool $flag=null);
}

interface IExpressionField extends IField {
    public function setAlias($alias);
    public function setAltSourceAlias($alias);
    public function getAltSourceAlias();
    public function getExpression();
    public function isNull();
}

interface ILateAttachField extends IField {}

interface IAttachmentField extends ILateAttachField {
    public function getAttachment();
}

interface ICombineField extends ILateAttachField {
    public function getCombine();
}

interface IVirtualField extends IField {
    public function getTargetFields();
    public function setTargetSourceAlias($alias);
    public function getTargetSourceAlias();
}


interface IFieldValueProcessor {
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null);
    public function deflateValue($value);
    public function sanitizeValue($value, opal\record\IRecord $forRecord=null);
    public function normalizeSavedValue($value, opal\record\IRecord $forRecord=null);
    public function compareValues($value1, $value2);
    public function generateInsertValue(array $row);
    public function getOrderableValue($outputValue);
    public function getSearchFieldType();
    public function canReturnNull();
}





// Clauses
interface IClauseProvider {
    public function isOr(bool $flag=null);
    public function isAnd(bool $flag=null);
    public function referencesSourceAliases(array $sourceAliases);
    public function getNonLocalFieldReferences();
}

interface IJoinClauseProvider extends IClauseProvider {}
interface IWhereClauseProvider extends IClauseProvider {}
interface IHavingClauseProvider extends IClauseProvider {}

interface IClause extends IJoinClauseProvider, IWhereClauseProvider, IHavingClauseProvider {
    public function setField(IField $field);
    public function getField();
    public static function isNegatedOperator($operator);
    public static function negateOperator($operator);
    public static function normalizeOperator($operator);
    public function setOperator($operator);
    public function getOperator();
    public function setValue($value);
    public function getValue();
    public function getPreparedValue();

    public static function mapVirtualClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr);
    public static function mapVirtualFieldClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, opal\query\IField $value, $isOr);
    public static function mapVirtualValueClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr);
}

interface IClauseList extends
    INestedComponent,
    IJoinClauseProvider,
    IWhereClauseProvider,
    IHavingClauseProvider,
    \Countable,
    core\IArrayProvider,
    ISourceProvider,
    core\lang\IChainable {

    public function getParent();
    public function getQuery();

    public function _addClause(IClauseProvider $clause=null);
    public function clear();
    public function endClause();

    public function referencesSourceAliases(array $sourceAliases);
    public function getNonLocalFieldReferences();
    public function getClausesFor(opal\query\ISource $source, opal\query\IClauseFactory $parent=null);
    public function extractClausesFor(opal\query\ISource $source, $checkValues=true);
    public function isLocalTo(array $sources);
}

interface IJoinClauseList extends IClauseList, IJoinClauseFactory, IParentSourceProvider {}
interface IWhereClauseList extends IClauseList, IWhereClauseFactory {}
interface IHavingClauseList extends IClauseList, IHavingClauseFactory {}


// Expressions
interface IExpression {
    public function getParentQuery();
    public function getParentExpression();
    public function getElements();

    public function op($operator);
    public function group(...$elements);
    public function express(...$elements);
    public function beginExpression(...$elements);
    public function correlate($targetField);
    public function isExpectingValue();
    public function addExpression(IExpression $expression);
    public function endExpression();
}

interface IExpressionOperator {
    const ADD = '+';
    const SUBTRACT = '-';
    const MULTIPLY = '*';
    const DIVIDE = '/';
    const MOD = '%';
    const POWER = '^';

    public function getOperator();
}

interface IExpressionValue {
    public function getValue();
}


// Search
interface ISearchController extends IField {
    public function setPhrase($phrase);
    public function getPhrase();
    public function getTerms();
    public function setFields(array $fields);
    public function addFields(array $fields);
    public function getFields();

    public function generateCaseList();
    public function getMaxScore();
    public function generateWhereClauseList();
}


// Paginator
interface IPaginator extends core\collection\IOrderablePaginator {
    public function setOrderableFields(...$fields);
    public function addOrderableFields(...$fields);
    public function getOrderableFields();
    //public function getOrderableFieldNames();

    public function setDefaultOrder(...$fields);
    public function getOrderDirectives();
    public function getOrderString();
    public function getFirstOrderDirective();
    public function getFirstOrderString();

    public function setDefaultLimit($limit);
    public function setDefaultOffset($offset);
    public function setKeyMap(array $map);
    public function applyWith($data);
    public function isApplied();
}


// Results
interface IBatchIterator extends core\collection\ICollection, \Iterator {
    public function getResult();
    public function isForFetch(bool $flag=null);

    public function getPrimarySource();
    public function addSources(array $joinSources);
    public function getSources();

    public function setPopulates(array $populates);
    public function getPopulates();

    public function setAttachments(array $attachments);
    public function getAttachments();

    public function setCombines(array $combines);
    public function getCombines();

    public function setListKeyField(IField $field=null);
    public function getListKeyField();

    public function setListValueField(IField $field=null);
    public function getListValueField();

    public function setNestFields(IField ...$fields);
    public function getNestFields();

    public function setBatchSize($size);
    public function getBatchSize();
}

interface IOutputManifest {
    public function getPrimarySource();
    public function getSources();
    public function importSource(ISource $source, array $rows=null, $isNormalized=true);

    public function addOutputField(IField $field);
    public function getOutputFields();
    public function getPrivateFields();
    public function getAllFields();

    public function getWildcardMap();

    public function getAggregateFields();
    public function getAggregateFieldAliases();
    public function hasAggregateFields();

    public function getOutputFieldProcessors();
    public function getCombines();



    public function getSearchController();

    public function queryRequiresPartial(bool $flag=null);
    public function requiresPartial($forFetch=true);
}


// Filters
interface IFilterConsumer {
    public function addFilter($a, $b=null);
    public function setFilter($name, $callback);
    public function hasFilter($name);
    public function removeFilter($name);
    public function getFilters();
    public function clearFilters();
    public function applyFilters(IQuery $query);
}

trait TFilterConsumer {

    protected $_filters = [];

    public function addFilter($a, $b=null) {
        if(is_string($a)) {
            $name = $a;
            $callback = $b;
        } else {
            $name = uniqid();
            $callback = $a;
        }

        return $this->setFilter($name, $callback);
    }

    public function setFilter($name, $callback) {
        $this->_filters[$name] = core\lang\Callback::factory($callback);
        return $this;
    }

    public function hasFilter($name) {
        return isset($this->_filters[$name]);
    }

    public function removeFilter($name) {
        unset($this->_filters[$name]);
        return $this;
    }

    public function getFilters() {
        return $this->_filters;
    }

    public function clearFilters() {
        $this->_filters = [];
        return $this;
    }

    public function applyFilters(IQuery $query) {
        if(!empty($this->_filters)) {
            $clause = $query->beginWhereClause();

            foreach($this->_filters as $filter) {
                $filter->invoke($clause, $this);
            }

            $clause->endClause();
        }

        return $this;
    }
}