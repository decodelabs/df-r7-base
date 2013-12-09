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
interface IInitiator extends core\IApplicationAware, ITransactionAware {
    public function beginSelect(array $fields=array());
    public function beginFetch();
    public function beginInsert($row);
    public function beginBatchInsert($rows=array());
    public function beginReplace($row);
    public function beginBatchReplace($rows=array());
    public function beginUpdate(array $valueMap=null);
    public function beginDelete();

    public function beginCorrelation(ISourceProvider $parent, $field, $alias=null);
    public function beginPopulate(IQuery $parent, array $fields, $type=IPopulateQuery::TYPE_ALL);

    public function beginJoin(IQuery $parent, array $fields=array(), $type=IJoinQuery::INNER);
    public function beginJoinConstraint(IQuery $parent, $type=IJoinQuery::INNER);
    public function beginAttach(IReadQuery $parent, array $fields=array());
    
    public function getFields();
    public function getFieldMap();
    public function getData(); 
    public function getParentQuery();
    public function getJoinType();

    public function setApplicator(Callable $applicator=null);
    public function getApplicator();
    
    public function from($sourceAdapter, $alias=null);
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
interface IEntryPoint {
    public function select($field1=null);
    public function selectDistinct($field1=null);
    public function fetch();
    public function insert($values);
    public function batchInsert($rows=array());
    public function replace($values);
    public function batchReplace($rows=array());
    public function update(array $valueMap=null);
    public function delete();
    
    public function begin();
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
interface IQuery extends ISourceProvider, ITransactionAware, user\IAccessLock, core\IChainable {
    public function getQueryType();
    public function importBlock($name);
}

interface IReadQuery extends IQuery, \IteratorAggregate, core\IArrayProvider {
    public function toKeyArray($keyField);
    public function toRow();
    public function getRawResult();
    public function getOutputManifest();
}

interface IWriteQuery extends IQuery {
    public function execute();
}

interface IDistinctQuery extends IQuery {
    public function isDistinct($flag=null);
}

interface ICorrelatableQuery extends IQuery {
    public function correlate($field, $alias=null);
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
    public function join($field1=null);
    public function leftJoin($field1=null);
    public function rightJoin($field1=null);
    public function addJoin(IJoinQuery $join);
}

interface IJoinConstrainableQuery extends IJoinProviderQuery {
    public function joinConstraint();
    public function leftJoinConstraint();
    public function rightJoinConstraint();
    public function addJoinConstraint(IJoinConstraintQuery $join);
}

interface IAttachableQuery extends IReadQuery {
    public function attach();
    public function addAttachment($name, IAttachQuery $attachment);
    public function getAttachments();
    public function clearAttachments();
}

interface IPopulatableQuery extends IQuery {
    public function populate($field1);
    public function populateSome($field);
    public function addPopulate(IPopulateQuery $populate);
    public function getPopulate($fieldName);
    public function getPopulates();
    public function clearPopulates();
}


interface IWhereClauseQuery extends IQuery, IWhereClauseFactory {}
interface IPrerequisiteClauseQuery extends IWhereClauseQuery, IPrerequisiteClauseFactory {}
interface IHavingClauseQuery extends IReadQuery, IHavingClauseFactory {}

interface IGroupableQuery extends IReadQuery {
    public function groupBy($field1);
    public function getGroupFields();
    public function clearGroupFields();
}

interface IOrderableQuery extends IQuery {
    public function orderBy($field1);
    public function setOrderDirectives(array $directives);
    public function getOrderDirectives();
    public function clearOrderDirectives();
    public function isPrimaryOrderSource($sourceAlias=null);
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
    
    public function addOutputFields($fields);
    public function getType();
    public function endJoin();
    public function referencesSourceAliases(array $sourceAliases);
}

interface IJoinConstraintQuery extends IJoinQuery {}


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
    public function endPopulate();
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
    public function setDirection($direction);
    public function isDescending($flag=null);
    public function isAscending($flag=null);
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
    IDistinctQuery,
    ICorrelatableQuery,
    IJoinableQuery, 
    IAttachableQuery, 
    IPopulatableQuery,
    IPrerequisiteClauseQuery,
    IGroupableQuery, 
    IHavingClauseQuery, 
    IOrderableQuery, 
    ILimitableQuery,
    IOffsettableQuery,
    IPageableQuery {
    public function addOutputFields($fields);
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
 * Fetch
 */
interface IFetchQuery extends 
    IReadQuery, 
    \Countable,
    ICorrelatableQuery,
    IJoinConstrainableQuery,
    IPopulatableQuery, 
    IPrerequisiteClauseQuery, 
    IOrderableQuery, 
    ILimitableQuery, 
    IOffsettableQuery,
    IPageableQuery {}
    
interface IFetchAttachQuery extends IFetchQuery, IAttachQuery {
    public function getListKeyField();
}


/***********
 * Insert
 */
interface IDataInsertQuery extends IWriteQuery {
    public function setRow($row);
    public function getRow();
}

interface IBatchDataInsertQuery extends IWriteQuery {
    public function addRows($rows);
    public function addRow($row);
    public function getRows();
    public function clearRows();
    public function getFields();
    public function getDereferencedFields();
    
    public function countPending();
    public function countInserted();
    public function countTotal();
    
    public function setFlushThreshold($flush);
    public function getFlushThreshold();
}


interface IInsertQuery extends IDataInsertQuery {
    public function ifNotExists($flag=null);
}

interface IReplaceQuery extends IDataInsertQuery {}
interface IBatchInsertQuery extends IBatchDataInsertQuery {}
interface IBatchReplaceQuery extends IBatchDataInsertQuery {}



/***********
 * Update
 */
interface IDataUpdateQuery extends IWriteQuery {
    public function set($field, $value=null);
    public function express($field, $element1);
    public function beginExpression($field, $element1);
    public function expressCorrelation($field, $targetField);
    public function getValueMap();
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
    IPrerequisiteClauseQuery, 
    ILimitableQuery {}



/***********
 * Source
 */
interface IQueryTypes {
    const SELECT = 1;
    const FETCH = 2;
    const INSERT = 3;
    const BATCH_INSERT = 4;
    const REPLACE = 5;
    const BATCH_REPLACE = 6;
    const UPDATE = 7;
    const DELETE = 8;
    
    const CORRELATION = 101;
    const POPULATE = 102;

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
}
 
 
interface IAdapterAware {
    public function getAdapter();
    public function getAdapterHash();
}
 
 
interface IAdapter extends user\IAccessLock {
    public function getQuerySourceId();
    public function getQuerySourceAdapterHash();
    public function getQuerySourceDisplayName();
    public function getDelegateQueryAdapter();
    
    public function supportsQueryType($type);
    public function supportsQueryFeature($feature);
    
    public function handleQueryException(IQuery $query, \Exception $e);
    public function ensureStorageConsistency();
    
    public function executeSelectQuery(ISelectQuery $query);
    public function countSelectQuery(ISelectQuery $query);
    public function executeFetchQuery(IFetchQuery $query);
    public function countFetchQuery(IFetchQuery $query);
    public function executeInsertQuery(IInsertQuery $query);
    public function executeBatchInsertQuery(IBatchInsertQuery $query);
    public function executeReplaceQuery(IReplaceQuery $query);
    public function executeBatchReplaceQuery(IBatchReplaceQuery $query);
    public function executeUpdateQuery(IUpdateQuery $query);
    public function executeDeleteQuery(IDeleteQuery $query);
    
    public function fetchRemoteJoinData(IJoinQuery $join, array $rows);
    public function fetchAttachmentData(IAttachQuery $attachment, array $rows);
    
    public function beginQueryTransaction();
    public function commitQueryTransaction();
    public function rollbackQueryTransaction();
    
    public function newRecord(array $values=null);
    
}

interface IIntegralAdapter extends IAdapter {
    public function getQueryAdapterSchema();

    public function prepareQueryClauseValue(IField $field, $value);
    public function rewriteVirtualQueryClause(IClauseFactory $parent, IVirtualField $field, $operator, $value, $isOr=false);

    public function getQueryResultValueProcessors(array $fields=null);
    public function applyQueryBlock(IQuery $query, $name, array $args);
}

interface IPaginatingAdapter extends IAdapter {
    public function applyPagination(IPaginator $paginator);
}


// Source
interface ISource extends IAdapterAware {
    public function getAlias();
    public function getId();
    public function getUniqueId();
    public function getHash();
    public function getDisplayName();
    
    public function handleQueryException(IQuery $query, \Exception $e);
    
    public function extrapolateIntegralAdapterField($name, $alias=null, opal\schema\IField $field=null);
    public function extrapolateIntegralAdapterFieldFromSchemaField($name, $alias, opal\schema\IField $field);

    public function addOutputField(IField $field);
    public function addPrivateField(IField $field);
    public function getFieldByAlias($alias);
    public function getFieldByQualifiedName($qName);
    public function getFirstOutputDataField();
    public function getLastOutputDataField();
    public function isOutputField(IField $field);
    
    public function getOutputFields();
    public function getDereferencedOutputFields();
    public function getPrivateFields();
    public function getDereferencedPrivateFields();
    public function getAllFields();
    public function getAllDereferencedFields();
}


// Manager
interface ISourceManager extends core\IApplicationAware, ITransactionAware {
    public function getPolicyManager();

    public function setParentSourceManager(ISourceManager $parent);
    public function getParentSourceManager();

    public function newSource($adapter, $alias, array $fields=null, $forWrite=false);
    public function removeSource($alias);
    public function getSources();
    public function getSourceByAlias($alias);
    public function countSourceAdapters();
    
    public function executeQuery(IQuery $query, Callable $executor);
    
    public function extrapolateSourceAdapter($adapter);
    public function extrapolateOutputField(ISource $source, $name);
    public function extrapolateField(ISource $source, $name);
    public function extrapolateIntrinsicField(ISource $source, $name, $checkAlias=null);
    public function extrapolateAggregateField(ISource $source, $name, $checkAlias=null);
    public function extrapolateDataField(ISource $source, $name, $checkAlias=null);
    public function generateAlias();
}



// Transaction
interface ITransaction extends IEntryPoint {
    public function commit();
    public function rollback();
    public function beginAgain();
    
    public function registerAdapter(IAdapter $adapter);
}


interface ITransactionAware {
    public function setTransaction(ITransaction $transaction=null);
    public function getTransaction();
}



// Fields
interface IField {
    public function getSource();
    public function getSourceAlias();
    
    public function getName();
    public function getAlias();
    public function hasDiscreetAlias();
    public function getQualifiedName();
    public function dereference();
    public function isOutputField();

    public function setLogicalAlias($alias);
    public function getLogicalAlias();

    public function setOverrideField(IField $field=null);
    public function getOverrideField();
}

trait TField {

    protected $_logicalAlias;
    protected $_overrideField;

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
}

interface IIntrinsicField extends IField {}
interface IWildcardField extends IField {}

interface ICorrelationField extends IField {
    public function getCorrelationQuery();
    public function getAggregateOutputField();
}

interface IAggregateField extends IField {
    public function getType();
    public function getTypeName();
    public function getTargetField();
    public function normalizeOutputValue($value);
    public function isDistinct($flag=null);
}

interface IAttachmentField extends IField {}

interface IVirtualField extends IField {
    public function getTargetFields();
}


interface IFieldValueProcessor {
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null);
    public function deflateValue($value);
    public function sanitizeValue($value, opal\record\IRecord $forRecord=null);
    public function normalizeSavedValue($value, opal\record\IRecord $forRecord=null);
    public function compareValues($value1, $value2);
    public function generateInsertValue(array $row);
}





// Clauses
interface IClauseProvider {
    public function isOr($flag=null);
    public function isAnd($flag=null);
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

interface IClauseMatcher {
    public function testRow(array $row);
    public function testRowMatch(array $row, array $joinRow);

    public static function compare($value, $operator, $compare);
}


interface IClauseList extends 
    INestedComponent,
    IJoinClauseProvider, 
    IWhereClauseProvider, 
    IHavingClauseProvider, 
    \Countable, 
    core\IArrayProvider, 
    ISourceProvider {

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


interface IExpression {
    public function getParentQuery();
    public function getParentExpression();
    public function getElements();

    public function op($operator);
    public function group($element1);
    public function express($element1);
    public function beginExpression($element1);
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

    public function getOperator();
}

interface IExpressionValue {
    public function getValue();
}


// Paginator
interface IPaginator extends core\collection\IOrderablePaginator {
    public function setOrderableFields($field1);
    public function addOrderableFields($field1);
    public function getOrderableFields();
    //public function getOrderableFieldNames();

    public function setDefaultOrder($field1);
    public function setDefaultLimit($limit);
    public function setDefaultOffset($offset);
    public function setKeyMap(array $map);
    public function applyWith($data);
}
