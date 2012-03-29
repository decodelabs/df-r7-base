<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\result;

use df;
use df\core;
use df\opal;

class ArrayManipulator implements IArrayManipulator {
    
    protected $_rows = array();
    protected $_isNormalized = false;
    
    protected $_outputManifest;
    
    public function __construct(opal\query\ISource $source, array $rows, $isNormalized=false, IOutputManifest $outputManifest=null) {
        $this->setRows($rows, $isNormalized);
        
        if(!$outputManifest) {
            $outputManifest = new OutputManifest($source, $rows, $isNormalized);
        } else {
            $outputManifest->importSource($source, $rows, $isNormalized);
        }
        
        $this->_outputManifest = $outputManifest;
    }
    
    public function setRows(array $rows, $isNormalized=true) {
        $this->_rows = $rows;
        $this->_isNormalized = $isNormalized;
        
        return $this;
    }
    
    public function getRows() {
        return $this->_rows;
    }
    
    public function getOutputManifest() {
        return $this->_outputManifest;
    }
    
    public function isEmpty() {
        return empty($this->_rows);
    }
    
    public function applyReadQuery(opal\query\IQuery $query, $keyField=null, $valField=null, $forCount=false) {
        if(empty($this->_rows)) {
            if($forCount) {
                $this->_rows = array('count' => 0);
            }
            
            return $this->_rows;
        }
        
        if($query instanceof opal\query\IJoinProviderQuery) {
            $this->applyJoins($query->getJoins());
        }
        
        if($query instanceof opal\query\IWhereClauseQuery && $query->hasWhereClauses()) {
            $this->applyWhereClauseList($query->getWhereClauseList());
            
            if(empty($this->_rows)) {
                if($forCount) {
                    $this->_rows = array('count' => 0);
                }
                
                return $this->_rows;
            }
        }
        
        if($query instanceof opal\query\IReadQuery) {
            $groups = $query instanceof opal\query\IGroupableQuery ? 
                $query->getGroupFields() : array();
                
            $this->applyAggregatesAndGroups($groups);
            
            if($query instanceof opal\query\IHavingClauseQuery && $query->hasHavingClauses()) {
                $this->applyHavingClauseList($query->getHavingClauseList());
                
                if(empty($this->_rows)) {
                    if($forCount) {
                        $this->_rows = array('count' => 0);
                    }
                    
                    return $this->_rows;
                }
            }
        }
        
        if($forCount) {
            $this->_rows = array('count' => count($this->_rows));
        } else {
            if($query instanceof opal\query\IOrderableQuery) {
                $this->applyOrderDirectives($query->getOrderDirectives());
            }
            
            if($query instanceof opal\query\ILimitableQuery) {
                $offset = null;
                
                if($query instanceof opal\query\IOffsettableQuery) {
                    $offset = $query->getOffset();
                }
                
                $this->applyLimit($query->getLimit(), $offset);
            }
            
            if($query instanceof opal\query\IAttachableQuery) {
                $attachments = $query->getAttachments();
                
                if(!empty($attachments)) {
                    $output = new BatchIterator(
                        $this->_outputManifest->getPrimarySource(), 
                        $this->_rows, 
                        $this->_outputManifest
                    );
                    
                    $output->setAttachments($attachments)
                        ->setListKeyField($keyField)
                        ->setListValueField($valField);
                        
                    return $output;
                }
            }
            
            $this->applyOutputFields(
                $keyField, $valField,
                $query instanceof opal\query\IFetchQuery
            );
        }

        return $this->_rows;
    }

    public function applyRemoteJoinQuery(opal\query\IQuery $query, array $localJoins, array $remoteJoins, $keyField=null, $valField=null, $forCount=false) {
        if(empty($this->_rows)) {
            if($forCount) {
                $this->_rows = array('count' => 0);
            }
            
            return $this->_rows;
        }
        
        foreach($localJoins as $join) {
            $this->_outputManifest->importSource($join->getSource());
        }
        
        $this->applyJoins($remoteJoins);
        
        if($query instanceof opal\query\IWhereClauseQuery && $query->hasWhereClauses()) {
            $this->applyWhereClauseList($query->getWhereClauseList());
            
            if(empty($this->_rows)) {
                return $this->_rows;
            }
        }
        
        
        if($query instanceof opal\query\IReadQuery) {
            $groups = $query instanceof opal\query\IGroupableQuery ? 
                $query->getGroupFields() : array();
                
            $this->applyAggregatesAndGroups($groups);
            
            if($query instanceof opal\query\IHavingClauseQuery && $query->hasHavingClauses()) {
                $this->applyHavingClauseList($query->getHavingClauseList());
                
                if(empty($this->_rows)) {
                    return $this->_rows;
                }
            }
        }
        
        if($forCount) {
            $this->_rows = array('count' => count($this->_rows));
        } else {
            if($query instanceof opal\query\IOrderableQuery) {
                $this->applyOrderDirectives($query->getOrderDirectives());
            }
            
            if($query instanceof opal\query\ILimitableQuery) {
                $offset = null;
                
                if($query instanceof opal\query\IOffsettableQuery) {
                    $offset = $query->getOffset();
                }
                
                $this->applyLimit($query->getLimit(), $offset);
            }
            
            if($query instanceof opal\query\IAttachableQuery) {
                $this->applyAttachments($query->getAttachments());
            }
            
            $this->applyOutputFields(
                $keyField, $valField,
                $query instanceof opal\query\IFetchQuery
            );
        }
        
        return $this->_rows;
    }

    public function applyAttachmentDataQuery(opal\query\IAttachQuery $query) {
        if(empty($this->_rows)) {
            return $this->_rows;
        }
        
        if($query instanceof opal\query\IJoinProviderQuery) {
            $this->applyJoins($query->getJoins());
        }
        
        if($query instanceof opal\query\IWhereClauseQuery && $query->hasWhereClauses()) {
            $this->applyWhereClauseList($query->getWhereClauseList());
            
            if(empty($this->_rows)) {
                return $this->_rows;
            }
        }
        
        if($query instanceof opal\query\IAttachableQuery) {
            $this->applyAttachments($query->getAttachments());
        }
        
        return $this->_rows;
    }

    public function applyBatchIteratorExpansion(IBatchIterator $batchIterator) {
        // TODO: populates
        
        $this->applyAttachments($batchIterator->getAttachments());
        
        $this->applyOutputFields(
            $batchIterator->getListKeyField(), 
            $batchIterator->getListValueField(), 
            $batchIterator->isForFetch()
        );
        
        return $this->_rows;
    }


// Joins
    public function applyJoins(array $joins) {
        $primarySource = $this->_outputManifest->getPrimarySource();
        $primarySourceId = $primarySource->getAdapter()->getQuerySourceId();
        $primarySourceAlias = $primarySource->getAlias();
        $sourceData = array();
        
        if(!($isNormalized = $this->_isNormalized)) {
            $primaryData = $this->_rows;
            $this->normalizeRows();
        }
        
        
        foreach($joins as $sourceAlias => $join) {
            $source = $join->getSource();
            $adapter = $source->getAdapter();
            
            if(!$isNormalized && $adapter->getQuerySourceId() == $primarySourceId) {
                $sourceData = array();
                
                foreach($primaryData as $row) {
                    $current = array();
                    
                    foreach($row as $key => $val) {
                        $current[$sourceAlias.'.'.$key] = $val;
                    }
                    
                    $sourceData[] = $current;
                }
            } else {
                $sourceData = $adapter->fetchRemoteJoinData($join, $this->_rows);
            }
            
            $this->_outputManifest->importSource($source, $sourceData, true);
            
            $rows = $this->_rows;
            $this->_rows = array();
            $clauseList = $join->getJoinClauseList()->toArray();
            $clauseIndex = $this->_createClauseIndex($clauseList);
            
            switch($join->getType()) {
                case opal\query\IJoinQuery::INNER:
                    foreach($rows as $row) {
                        foreach($sourceData as $joinRow) {
                            if($this->_testRowMatchWithClauseIndex($clauseIndex, $row, $joinRow)) {
                                $this->_rows[] = array_merge($row, $joinRow);
                            }
                        }
                    }
                    
                    break;
                    
                case opal\query\IJoinQuery::LEFT:
                    foreach($rows as $row) {
                        $match = false;
                        
                        foreach($sourceData as $joinRow) {
                            if($this->_testRowMatchWithClauseIndex($clauseIndex, $row, $joinRow)) {
                                $this->_rows[] = array_merge($row, $joinRow);
                                $match = true;
                            }
                        }
                        
                        if(!$match) {
                            $this->_rows[] = $row;
                        }
                    }
                    
                    break;
                    
                case opal\query\IJoinQuery::RIGHT:
                    foreach($sourceData as $joinRow) {
                        $match = false;
                        
                        foreach($rows as $row) {
                            if($this->_testRowMatchWithClauseIndex($clauseIndex, $row, $joinRow)) {
                                $this->_rows[] = array_merge($row, $joinRow);
                                $match = true;
                            }
                        }
                        
                        if(!$match) {
                            $this->_rows[] = $joinRow;
                        }
                    }

                    break;
            }
        }

        return $this;
    }


// Normalize
    public function normalizeRows() {
        if($this->_isNormalized || empty($this->_rows)) {
            $this->_isNormalized = true;
            return $this;
        }
        
        $sourceAlias = $this->_outputManifest->getPrimarySource()->getAlias();
        
        $rows = $this->_rows;
        $this->_rows = array();
        
        foreach($rows as $i => $row) {
            $current = array();
            
            foreach($row as $key => $val) {
                $current[$sourceAlias.'.'.$key] = $val;
            }
            
            $this->_rows[$i] = $current;
        }
        
        $this->_isNormalized = true;
        
        return $this;
    }


// Where
    public function applyWhereClauseList(opal\query\IWhereClauseList $clauseList) {
        if(empty($this->_rows)) {
            return $this;
        }
        
        $this->normalizeRows();
        
        if(!$clauseList->isEmpty()) {
            $clauseIndex = $this->_createClauseIndex($clauseList->toArray());
            
            foreach($this->_rows as $i => $row) {
                if(!$this->_testRowWithClauseIndex($clauseIndex, $row)) {
                    unset($this->_rows[$i]);
                }
            }
        }
        
        return $this;
    }
    
    
    
// Groups
    public function applyAggregatesAndGroups(array $groupFields=array()) {
        if(empty($this->_rows) || (!$this->_outputManifest->hasAggregateFields() && empty($groupFields))) {
            return $this;
        }
        
        $this->normalizeRows();
        $fields = $this->_outputManifest->getAllFields();
        $aggregateFields = $this->_outputManifest->getAggregateFieldAliases();
        
        // init aggregates
        foreach($this->_rows as &$row) {
            foreach($aggregateFields as $alias => $qName) {
                $fieldName = $fields[$alias]->getTargetField()->getQualifiedName();
                
                if(isset($row[$fieldName])) {
                    $row[$qName] = $row[$fieldName];
                } else {
                    $row[$qName] = null;
                }
            }
            
            unset($row);
        }
        
        
        // partition
        if(!empty($groupFields)) {
            $rowGroups = array();
            $delim = "\xFF";
            
            foreach($this->_rows as $row) {
                $compVal = '';
                
                foreach($groupFields as $groupField) {
                    $fieldName = $groupField->getQualifiedName();
                    
                    if(isset($row[$fieldName])) {
                        $compVal .= $row[$fieldName];
                    }
                    
                    $compVal .= $delim;
                }
                
                $rowGroups[$compVal][] = $row;
            }
            
            $rowGroups = array_values($rowGroups);
        } else {
            $rowGroups = array($this->_rows);
        }
        
        
        // re-list
        $this->_rows = array();
        
        foreach($rowGroups as $group) {
            $row = $group[0];
            
            if(!empty($aggregateFields)) {
                $aggregateData = array();
                
                foreach($group as $groupRow) {
                    foreach($aggregateFields as $alias => $qName) {
                        $aggregateData[$qName][] = $groupRow[$qName];
                    }
                }
                
                foreach($aggregateFields as $alias => $qName) {
                    $field = $fields[$alias];
                        
                    switch($field->getType()) {
                        case opal\query\field\Aggregate::TYPE_COUNT:
                            $row[$qName] = count($aggregateData[$qName]);
                            break;
                            
                        case opal\query\field\Aggregate::TYPE_SUM:
                            $row[$qName] = array_sum($aggregateData[$qName]);
                            break;
                        
                        case opal\query\field\Aggregate::TYPE_AVG:
                            $row[$qName] = array_sum($aggregateData[$qName]) / count($aggregateData[$qName]);
                            break;
                            
                        case opal\query\field\Aggregate::TYPE_MIN:
                            $row[$qName] = min($aggregateData[$qName]);
                            break;
                            
                        case opal\query\field\Aggregate::TYPE_MAX:
                            $row[$qName] = max($aggregateData[$qName]);
                            break;
                    }
                }
            }

            $this->_rows[] = $row;
        }

        return $this;
    }


// Having
    public function applyHavingClauseList(opal\query\IHavingClauseList $clauseList) {
        if(empty($this->_rows)) {
            return $this;
        }
        
        $this->normalizeRows();
        
        if(!$clauseList->isEmpty()) {
            $clauseIndex = $this->_createClauseIndex($clauseList->toArray());
            
            foreach($this->_rows as $i => $row) {
                if(!$this->_testRowWithClauseIndex($clauseIndex, $row)) {
                    unset($this->_rows[$i]);
                }
                
                unset($row);
            }
        }
        
        return $this;
    }
    
    
// Order
    public function applyOrderDirectives(array $orderDirectives) {
        if(empty($this->_rows) || empty($orderDirectives)) {
            return $this;
        }
        
        $this->normalizeRows();
        
        $sortFields = array();
        $sortData = array();
        
        foreach($orderDirectives as $directive) {
            if(!$directive instanceof opal\query\IOrderDirective) {
                continue;
            }
            
            $field = $directive->getField();
            $sortFieldName = $field->getQualifiedName();
            
            if(isset($sortFields[$sortFieldName])) {
                continue;
            }
            
            $sortFields[$sortFieldName] = $directive->isDescending() ? SORT_DESC : SORT_ASC;
        }
        
        foreach($this->_rows as &$row) {
            foreach($sortFields as $field => $direction) {
                if(isset($row[$field])) {
                    $sortData[$field][] = &$row[$field];
                } else {
                    $sortData[$field][] = null;
                }
            }
        }
        
        $args = array();
    
        foreach($sortFields as $field => $direction) {
            $args[] = &$sortData[$field];
            $args[] = $direction;
        }
        
        $args[] = &$this->_rows;
        call_user_func_array('array_multisort', $args);
        
        return $this;
    }


// Limit
    public function applyLimit($limit, $offset) {
        if(empty($this->_rows)) {
            return $this;
        }
        
        $this->normalizeRows();
        $this->_rows = array_slice($this->_rows, $offset, $limit);
        
        return $this;
    }
    
    
// Attachments
    public function applyAttachments(array $attachments) {
        if(empty($this->_rows) || empty($attachments)) {
            return $this;
        }
        
        $this->normalizeRows();
        
        foreach($attachments as $attachKey => $attachment) {
            $this->_outputManifest->addOutputField(new opal\query\field\Attachment($attachKey, $attachment));
            $source = $attachment->getSource();
            $adapter = $source->getAdapter();
            
            try {
                $sourceData = $adapter->fetchAttachmentData($attachment, $this->_rows);
            } catch(\Exception $e) {
                if($source->handleQueryException($attachment, $e)) {
                    $sourceData = $adapter->fetchAttachmentData($attachment, $this->_rows);
                } else {
                    throw $e;
                }
            }
            
            $manipulator = new self($source, $sourceData, true);
            $clauseList = $attachment->getJoinClauseList()->toArray();
            $clauseIndex = $this->_createClauseIndex($clauseList);
            $isFetchQuery = $attachment instanceof opal\query\IFetchQuery;
            $isTypeOneAttachment = $attachment->getType() === 0;
            
            
            // Get key / val fields
            $keyField = $valField = null;
                
            if($attachment instanceof opal\query\ISelectAttachQuery) {
                $keyField = $attachment->getListKeyField();
                $valField = $attachment->getListValueField();
            }
            
            
            // Import sources to child manipulator
            if($attachment instanceof opal\query\IJoinProviderQuery) {
                foreach($attachment->getJoins() as $join) {
                    $manipulator->getOutputManifest()->importSource($join->getSource());
                }
            }
            
            
            // Prepare groups & having clauses
            if($isReadQuery = $attachment instanceof opal\query\IReadQuery) {
                $groups = $attachment instanceof opal\query\IGroupableQuery ? 
                    $attachment->getGroupFields() : array();
                    
                $havingClauseList = null;
                
                if($attachment instanceof opal\query\IHavingClauseQuery && $attachment->hasHavingClauses()) {
                    $havingClauseList = $attachment->getHavingClauseList();
                }
            }
            
            
            // Prepare order directives
            $orderDirectives = $attachment instanceof opal\query\IOrderableQuery ?
                $attachment->getOrderDirectives() : null;
                
                
            // Prepare limit & offset
            $limit = $offset = $canLimit = null;
            
            if($attachment instanceof opal\query\ILimitableQuery) {
                $limit = $attachment->getLimit();
                
                if($attachment instanceof opal\query\IOffsettableQuery) {
                    $offset = $attachment->getOffset();
                }
                
                if($isTypeOneAttachment) {
                    $limit = 1;
                }
                
                $canLimit = $limit !== null || $offset !== null;
            }
              
                
            // Prepare child attachments
            $childAttachments = $attachment instanceof opal\query\IAttachableQuery ?
                $attachment->getAttachments() : null;
                
                
            
            
            // Iterate data
            foreach($this->_rows as &$row) {
                
                // Filter source data
                if(empty($clauseList)) {
                    $attachData = $sourceData;
                } else {
                    $attachData = array();
                    
                    foreach($sourceData as $joinRow) {
                        if($this->_testRowMatchWithClauseIndex($clauseIndex, $row, $joinRow)) {
                            $attachData[] = $joinRow;
                        }
                    }
                }
                
                $manipulator->setRows($attachData);
                
                // Groups, aggregates and having clauses
                if($isReadQuery) {
                    $manipulator->applyAggregatesAndGroups($groups);
                    
                    if($havingClauseList !== null) {
                        $manipulator->applyHavingClauseList($havingClauseList);
                    }
                }
                
                
                // Order directives
                if(!empty($orderDirectives)) {
                    $manipulator->applyOrderDirectives($orderDirectives);
                }
                
                
                // Limit & offset
                if($canLimit) {
                    $manipulator->applyLimit($limit, $offset);
                }
                
                
                // Child attachments
                if(!empty($childAttachments)) {
                    $manipulator->applyAttachments($childAttachments);
                }
                
                
                // Format output
                $attachData = $manipulator->applyOutputFields($keyField, $valField, $isFetchQuery)->getRows();
                    
                if($isTypeOneAttachment) {
                    $attachData = array_shift($attachData);
                }
                
                $row[$attachKey] = $attachData;
            }
        }

        return $this;
    }
    
    
// Output
    public function applyOutputFields(opal\query\IField $keyField=null, opal\query\IField $valField=null, $forFetch=false) {
        if(empty($this->_rows)) {
            return $this;
        }
        
        $this->normalizeRows();
        
        $temp = $this->_rows;
        $this->_rows = array();
        
        
        // Prepare helpers
        $primarySource = $this->_outputManifest->getPrimarySource();
        $primaryAdapter = $primarySource->getAdapter();
        $outputFields = $this->_outputManifest->getOutputFields();
        $aggregateFields = $this->_outputManifest->getAggregateFieldAliases();
        $wildcards = $this->_outputManifest->getWildcardMap();
        $fieldProcessors = $this->_outputManifest->getOutputFieldProcessors();
        
        
        // Prepare qualified names
        $qNameMap = array();
        
        foreach($outputFields as $alias => $field) {
            $qNameMap[$alias] = $field->getQualifiedName();
        }
        
        
        // Prepare key / val field
        $keyName = $valQName = null;
        
        if($keyField) {
            $keyName = $keyField->getQualifiedName();
        }
        
        if($valField) {
            $valQName = $valField->getQualifiedName();
            $valName = $valField->getName();
        }
        
        
        // Prepare object field
        $objectKey = $primarySource->getAlias().'.@object';
        $fetchObject = false;
        
        if($forFetch) {
            $fetchObject = isset($temp[0][$objectKey]);
        }
        
        
        // Iterate data
        foreach($temp as $row) {
            
            // Pre-process row
            if(!empty($fieldProcessors)) {
                $tempRow = $row;
                $row = array();
                
                foreach($qNameMap as $alias => $qName) {
                    if(isset($fieldProcessors[$qName])) {
                        $row[$qName] = $fieldProcessors[$qName]->inflateValueFromRow(
                            $qName, $tempRow, $forFetch
                        );
                    } else if(isset($tempRow[$qName])) {
                        $row[$qName] = $tempRow[$qName];
                    } else {
                        $row[$alias] = null;
                    }
                }
            }
            
            
            // Single value output
            if($valQName) { 
                if(array_key_exists($valQName, $row)) {
                    $current = $row[$valQName];
                } else if(isset($row[$valName])) {
                    $current = $row[$valName];
                } else {
                    $current = null;
                }
                
            // Entity object
            } else if($fetchObject) { 
                if(isset($row[$objectKey])) {
                    $current = $row[$objectKey];
                } else {
                    $current = null;
                }
                
                
            // Normal row
            } else { 
                $current = array();
                
                // Apply wildcards
                if(!empty($wildcards)) {
                    foreach($row as $key => $value) {
                        if(isset($aggregateFields[$key])) {
                            continue;
                        }
                        
                        $parts = explode('.', $key, 2);
                        $fieldName = array_pop($parts);
                        $s = array_shift($parts);
                        
                        if((empty($s) || isset($wildcards[$s]))
                        && substr($fieldName, 0, 1) != '@') {
                            $current[$fieldName] = $value;
                        }
                    }
                }
                
                
                // Add known fields
                foreach($qNameMap as $alias => $qName) {
                    if(isset($row[$qName])) {
                        $current[$alias] = $row[$qName];
                    } else if(!isset($current[$alias])) {
                        $current[$alias] = null;
                    }
                }
                
                
                // Convert to record object
                if($forFetch) {
                    $record = $primaryAdapter->newRecord();
                    $current = $record->populateWithPreparedData($current);
                }
            }

            
            // Add row to output
            if($keyName) {
                if(isset($row[$keyName])) {
                    $key = $row[$keyName];
                } else if(isset($row[$keyName = $keyField->getAlias()])) {
                    $key = $row[$keyName];
                }
                
                $this->_rows[$key] = $current;
            } else {
                $this->_rows[] = $current;
            }
        }
        
        return $this;
    }


    
// Clause tests
    protected function _testRowWithClauseIndex(array $clauseIndex, array $row) {
        if(empty($clauseIndex)) {
            return true;
        }
        
        foreach($clauseIndex as $set) {
            $test = true;
            
            foreach($set as $clause) {
                if(is_array($clause)) {
                    $test &= $this->_testRowWithClauseIndex($clause, $row);
                } else {
                    $test &= $this->_testRowWithClause($clause, $row);
                }
            }
            
            if($test) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function _testRowMatchWithClauseIndex(array $clauseIndex, array $row, array $joinRow) {
        if(empty($clauseIndex)) {
            return true;
        }
        
        foreach($clauseIndex as $set) {
            $test = true;
            
            foreach($set as $clause) {
                if(is_array($clause)) {
                    $test &= $this->_testRowMatchWithClauseIndex($clause, $row, $joinRow);
                } else {
                    $test &= $this->_testRowMatchWithClause($clause, $row, $joinRow);
                }
            }
            
            if($test) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function _testRowWithClause(opal\query\IClause $clause, array $row) {
        $field = $clause->getField();
        $qName = $field->getQualifiedName();
        
        if(!isset($row[$qName])) {
            $value = null;
        } else {
            $value = $row[$qName];
        }
        
        $compare = $clause->getPreparedValue();
        
        if($compare instanceof opal\query\IField) {
            core\stub('Field comparison');
        }
        
        if($compare instanceof opal\query\ISelectQuery) {
            $source = $compare->getSource();
            
            if(null === ($targetField = $source->getFirstOutputDataField())) {
                throw new opal\query\ValueException(
                    'Clause subquery does not have a distinct return field'
                );
            }
            
            
            switch($clause->getOperator()) {
                case opal\query\clause\Clause::OP_EQ:
                case opal\query\clause\Clause::OP_NEQ:
                case opal\query\clause\Clause::OP_LIKE:
                case opal\query\clause\Clause::OP_NOT_LIKE:
                case opal\query\clause\Clause::OP_CONTAINS:
                case opal\query\clause\Clause::OP_NOT_CONTAINS:
                case opal\query\clause\Clause::OP_BEGINS:
                case opal\query\clause\Clause::OP_NOT_BEGINS:
                case opal\query\clause\Clause::OP_ENDS:
                case opal\query\clause\Clause::OP_NOT_ENDS:
                    $limit = $compare->getLimit();
                    $compare->limit(1);
                    $clause->setValue($compare->toValue($targetField->getName()));
                    $compare->limit($limit);
                    break;
                    
                case opal\query\clause\Clause::OP_IN:
                case opal\query\clause\Clause::OP_NOT_IN:
                    $clause->setValue($compare->toList($targetField->getName()));
                    break;
                    
                case opal\query\clause\Clause::OP_GT:
                case opal\query\clause\Clause::OP_GTE:
                    $source = $compare->getSource();
                    $source->addOutputField(
                        new opal\query\field\Aggregate(
                            $source, 'MAX', $targetField, 'max'
                        )
                    );
                    
                    $clause->setValue($compare->toValue('max'));
                    break;
                    
                case opal\query\clause\Clause::OP_LT: 
                case opal\query\clause\Clause::OP_LTE:
                    $source = $compare->getSource();
                    $source->addOutputField(
                        new opal\query\field\Aggregate(
                            $source, 'MIN', $targetField, 'min'
                        )
                    );
                    
                    $clause->setValue($compare->toValue('min'));
                    break;
                    
                    
                case opal\query\clause\Clause::OP_BETWEEN:
                case opal\query\clause\Clause::OP_NOT_BETWEEN:
                    throw new OperatorException(
                        'Operator '.$operator.' is not valid for clause subqueries'
                    );
                    
                
                default:
                    throw new opal\query\OperatorException(
                        'Operator '.$operator.' is not recognized'
                    );
            }

            $compare = $clause->getPreparedValue();
        }

        return $this->_testClauseValues($clause, $value, $compare);
    }

    protected function _testRowMatchWithClause(opal\query\IClause $clause, array $row, array $joinRow) {
        $leftField = $clause->getField()->getQualifiedName();
        $rightField = $clause->getValue()->getQualifiedName();
        
        $value = isset($joinRow[$leftField]) ? $joinRow[$leftField] : null;
        $compare = isset($row[$rightField]) ? $row[$rightField] : null;
        
        return $this->_testClauseValues($clause, $value, $compare);
    }

    
    protected function _testClauseValues(opal\query\IClause $clause, $value, $compare) {
        switch($clause->getOperator()) {
            case opal\query\clause\Clause::OP_EQ:
                return $value == $compare;
                
            case opal\query\clause\Clause::OP_NEQ:
                return $value != $compare;
                
            case opal\query\clause\Clause::OP_GT:
                return $value > $compare;
                
            case opal\query\clause\Clause::OP_GTE:
                return $value >= $compare;
                
            case opal\query\clause\Clause::OP_LT: 
                return $value < $compare;
                
            case opal\query\clause\Clause::OP_LTE:
                return $value <= $compare;
            
            case opal\query\clause\Clause::OP_IN:
                return in_array($value, $compare);
                
            case opal\query\clause\Clause::OP_NOT_IN:
                return !in_array($value, $compare);
            
            case opal\query\clause\Clause::OP_BETWEEN:
                return $compare[0] <= $value && $value <= $compare[1];
                
            case opal\query\clause\Clause::OP_NOT_BETWEEN:
                return !($compare[0] <= $value && $value <= $compare[1]);
            
            case opal\query\clause\Clause::OP_LIKE:
                return core\string\Util::likeMatch($compare, $value);
                
            case opal\query\clause\Clause::OP_NOT_LIKE:
                return !core\string\Util::likeMatch($compare, $value);
            
            case opal\query\clause\Clause::OP_CONTAINS:
                return core\string\Util::contains($compare, $value);
                
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
                return !core\string\Util::contains($compare, $value);
            
            case opal\query\clause\Clause::OP_BEGINS:
                return core\string\Util::begins($compare, $value);
                
            case opal\query\clause\Clause::OP_NOT_BEGINS:
                return !core\string\Util::begins($compare, $value);
            
            case opal\query\clause\Clause::OP_ENDS:
                return core\string\Util::ends($compare, $value);
                
            case opal\query\clause\Clause::OP_NOT_ENDS:
                return !core\string\Util::ends($compare, $value);
            
            
            default:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not recognized'
                );
        }
    }


    protected function _createClauseIndex(array $clauses) {
        $clauseIndex = array();
        $set = array();
        
        foreach($clauses as $clause) {
            if($clause->isOr() && !empty($set)) {
                $clauseIndex[] = $set;
                $set = array();
            }
            
            if($clause instanceof opal\query\IClauseList) {
                $clause = $this->_createClauseIndex($clause->toArray());
                
                if(empty($clause)) {
                    continue;
                }
            }
            
            $set[] = $clause;
        }
        
        if(!empty($set)) {
            $clauseIndex[] = $set;
        }
        
        return $clauseIndex;
    }
}
