<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\result;

use df;
use df\core;
use df\opal;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Result iterator
interface IBatchIterator extends core\collection\ICollection, \IteratorAggregate {
    public function getResult();
    public function isForFetch($flag=null);
    
    public function getPrimarySource();
    public function addSources(array $joinSources);
    public function getSources();
    
    public function setPopulates(array $populates);
    public function getPopulates();

    public function setAttachments(array $attachments);
    public function getAttachments();

    public function setCombines(array $combines);
    public function getCombines();
    
    public function setListKeyField(opal\query\IField $field=null);
    public function getListKeyField();
    
    public function setListValueField(opal\query\IField $field=null);
    public function getListValueField();
    
    public function setBatchSize($size);
    public function getBatchSize();
}


interface IOutputManifest {
    public function getPrimarySource();
    public function getSources();
    public function importSource(opal\query\ISource $source, array $rows=null, $isNormalized=true);
    
    public function addOutputField(opal\query\IField $field);
    public function getOutputFields();
    public function getPrivateFields();
    public function getAllFields();
    
    public function getWildcardMap();
    
    public function getAggregateFields();
    public function getAggregateFieldAliases();
    public function hasAggregateFields();
    
    public function getOutputFieldProcessors();
    public function getCombines();

    public function queryRequiresPartial($flag=null);
    public function requiresPartial($forFetch=true);
}


interface IArrayManipulator {
    public function setRows(array $rows, $isNormalized=true);
    public function getRows();
    public function getOutputManifest();
    public function isEmpty();
    
    public function applyReadQuery(opal\query\IQuery $query, $keyField=null, $valField=null);
    public function applyRemoteJoinQuery(opal\query\IQuery $query, array $localJoins, array $remoteJoins);
    public function applyAttachmentDataQuery(opal\query\IAttachQuery $query);
    public function applyBatchIteratorExpansion(IBatchIterator $batchIterator, $batchNumber);
    
    public function normalizeRows();
    public function applyJoins(array $joins);
    public function applyWhereClauseList(opal\query\IWhereClauseList $clauseList);
    public function applyAggregatesAndGroups(array $groupFields=[]);
    public function applyHavingClauseList(opal\query\IHavingClauseList $clauseList);
    public function applyDistinct();
    public function applyOrderDirectives(array $orderDirectives);
    public function applyLimit($limit, $offset);
    public function applyPopulates(array $populates);
    public function applyAttachments(array $attachments);
    public function applyCombines(array $combines);
    public function applyOutputFields(opal\query\IField $keyField=null, opal\query\IField $valField=null, $forFetch=false);
}
