<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\native;

use df;
use df\core;
use df\opal;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IArrayManipulator {
    public function setRows(array $rows, $isNormalized=true);
    public function getRows();
    public function getOutputManifest();
    public function isEmpty();
    
    public function applyReadQuery(opal\query\IQuery $query, $keyField=null, $valField=null);
    public function applyRemoteJoinQuery(opal\query\IQuery $query, array $localJoins, array $remoteJoins);
    public function applyAttachmentDataQuery(opal\query\IAttachQuery $query);
    public function applyBatchIteratorExpansion(opal\query\IBatchIterator $batchIterator, $batchNumber);
    
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

interface IClauseMatcher {
    public function testRow(array $row, array &$matchedFields);
    public function testRowMatch(array $row, array $joinRow);

    public static function compare($value, $operator, $compare);
}