<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\native;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;

use df\core;
use df\opal;

class ArrayManipulator implements IArrayManipulator
{
    protected $_rows = [];
    protected $_isNormalized = false;

    protected $_outputManifest;

    public function __construct(opal\query\ISource $source, array $rows, $isNormalized = false, opal\query\IOutputManifest $outputManifest = null)
    {
        $this->setRows($rows, $isNormalized);

        if (!$outputManifest) {
            $outputManifest = new opal\query\OutputManifest($source, /*$rows*/ null, $isNormalized);
        } else {
            $outputManifest->importSource($source, $rows, $isNormalized);
        }

        $this->_outputManifest = $outputManifest;
    }

    public function setRows(array $rows, $isNormalized = true)
    {
        $this->_rows = $rows;
        $this->_isNormalized = $isNormalized;

        return $this;
    }

    public function getRows()
    {
        return $this->_rows;
    }

    public function getOutputManifest()
    {
        return $this->_outputManifest;
    }

    public function isEmpty(): bool
    {
        return empty($this->_rows);
    }

    public function applyReadQuery(opal\query\IQuery $query, $keyField = null, $valField = null, $forCount = false)
    {
        if (empty($this->_rows)) {
            if ($forCount) {
                $this->_rows = ['count' => 0];
            }

            return $this->_rows;
        }

        if ($query instanceof opal\query\IJoinProviderQuery) {
            $this->applyJoins($query->getJoins());
        }

        if ($query instanceof opal\query\IWhereClauseQuery && $query->hasWhereClauses()) {
            $this->applyWhereClauseList($query->getWhereClauseList());

            if (empty($this->_rows)) {
                if ($forCount) {
                    $this->_rows = ['count' => 0];
                }

                return $this->_rows;
            }
        }

        if ($query instanceof opal\query\IReadQuery) {
            if ($query instanceof opal\query\IGroupableQuery) {
                $groups = $query->getGroupFields();
            } else {
                $groups = [];
            }

            $this->applyAggregatesAndGroups($groups);

            if ($query instanceof opal\query\IHavingClauseQuery && $query->hasHavingClauses()) {
                $this->applyHavingClauseList($query->getHavingClauseList());

                if (empty($this->_rows)) {
                    if ($forCount) {
                        $this->_rows = ['count' => 0];
                    }

                    return $this->_rows;
                }
            }

            if ($query instanceof opal\query\IDistinctQuery && $query->isDistinct()) {
                $this->applyDistinct();
            }
        }


        if ($forCount) {
            $this->_rows = ['count' => count($this->_rows)];
        } else {
            if ($query instanceof opal\query\IOrderableQuery) {
                $this->applyOrderDirectives($query->getOrderDirectives());
            }

            if ($query instanceof opal\query\ILimitableQuery) {
                $offset = null;

                if ($query instanceof opal\query\IOffsettableQuery) {
                    $offset = $query->getOffset();
                }

                $this->applyLimit($query->getLimit(), $offset);
            }

            if ($query instanceof opal\query\IAttachProviderQuery) {
                $attachments = $query->getAttachments();

                if (!empty($attachments)) {
                    $output = new opal\query\BatchIterator(
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

            if ($query instanceof opal\query\ICombinableQuery) {
                $this->applyCombines($query->getCombines());
            }

            /*
            $this->applyOutputFields(
                $keyField, $valField, $query->getNestFields(),
                $query instanceof opal\query\IFetchQuery
            );
             */
        }

        return $this->_rows;
    }

    public function applyRemoteJoinQuery(opal\query\IQuery $query, array $localJoins, array $remoteJoins, $forCount = false)
    {
        if (empty($this->_rows)) {
            if ($forCount) {
                $this->_rows = ['count' => 0];
            }

            return $this->_rows;
        }

        foreach ($localJoins as $join) {
            $this->_outputManifest->importSource($join->getSource());
        }

        $this->applyJoins($remoteJoins);

        if ($query instanceof opal\query\IWhereClauseQuery && $query->hasWhereClauses()) {
            $this->applyWhereClauseList($query->getWhereClauseList());

            if (empty($this->_rows)) {
                return $this->_rows;
            }
        }


        if ($query instanceof opal\query\IReadQuery) {
            if ($query instanceof opal\query\IGroupableQuery) {
                $groups = $query->getGroupFields();
            } else {
                $groups = [];
            }

            $this->applyAggregatesAndGroups($groups);

            if ($query instanceof opal\query\IHavingClauseQuery && $query->hasHavingClauses()) {
                $this->applyHavingClauseList($query->getHavingClauseList());

                if (empty($this->_rows)) {
                    return $this->_rows;
                }
            }
        }

        if ($forCount) {
            $this->_rows = ['count' => count($this->_rows)];
        } else {
            if ($query instanceof opal\query\IOrderableQuery) {
                $this->applyOrderDirectives($query->getOrderDirectives());
            }

            if ($query instanceof opal\query\ILimitableQuery) {
                $offset = null;

                if ($query instanceof opal\query\IOffsettableQuery) {
                    $offset = $query->getOffset();
                }

                $this->applyLimit($query->getLimit(), $offset);
            }
        }

        return $this->_rows;
    }

    public function applyAttachmentDataQuery(opal\query\IAttachQuery $query, $joinsApplied = false, $clausesApplied = false)
    {
        if (empty($this->_rows)) {
            return $this->_rows;
        }

        if (!$joinsApplied && $query instanceof opal\query\IJoinProviderQuery) {
            $this->applyJoins($query->getJoins());
        }

        if (!$clausesApplied && $query instanceof opal\query\IWhereClauseQuery && $query->hasWhereClauses()) {
            $this->applyWhereClauseList($query->getWhereClauseList());

            if (empty($this->_rows)) {
                return $this->_rows;
            }
        }

        return $this->_rows;
    }

    public function applyBatchIteratorExpansion(opal\query\IBatchIterator $batchIterator, $batchNumber)
    {
        $this->applyPopulates($batchIterator->getPopulates());
        $this->applyAttachments($batchIterator->getAttachments());
        $this->applyCombines($batchIterator->getCombines());

        $this->applyOutputFields(
            $batchIterator->getListKeyField(),
            $batchIterator->getListValueField(),
            $batchIterator->getNestFields(),
            $batchIterator->isForFetch(),
            $batchIterator->getFormatter()
        );

        return $this->_rows;
    }


    // Joins
    public function applyJoins(array $joins)
    {
        $primarySource = $this->_outputManifest->getPrimarySource();
        $primarySourceId = $primarySource->getAdapter()->getQuerySourceId();
        $primarySourceAlias = $primarySource->getAlias();
        $primaryData = $this->_rows;
        $sourceData = [];

        if (!($isNormalized = $this->_isNormalized)) {
            $this->normalizeRows();
        }


        foreach ($joins as $sourceAlias => $join) {
            $source = $join->getSource();
            $adapter = $source->getAdapter();

            if (!$isNormalized && $adapter->getQuerySourceId() == $primarySourceId) {
                $sourceData = [];

                foreach ($primaryData as $row) {
                    $current = [];

                    foreach ($row as $key => $val) {
                        $current[$sourceAlias . '.' . $key] = $val;
                    }

                    $sourceData[] = $current;
                }
            } else {
                $sourceData = $adapter->fetchRemoteJoinData($join, $this->_rows);

                if ($sourceData instanceof core\IArrayProvider) {
                    $sourceData = $sourceData->toArray();
                }

                if (!is_array($sourceData)) {
                    throw Exceptional::UnexpectedValue(
                        'Invalid source data for remote join'
                    );
                }
            }

            $this->_outputManifest->importSource($source, $sourceData, true);

            $rows = $this->_rows;
            $this->_rows = [];


            $onClauses = $join->getJoinClauseList();
            $whereClauses = $join->getWhereClauseList();
            $onClausesEmpty = $onClauses->isEmpty();
            $whereClausesEmpty = $whereClauses->isEmpty();
            $clauses = null;

            if (!$onClausesEmpty && !$whereClausesEmpty) {
                $clauses = new opal\query\clause\ListBase($join);
                $clauses->_addClause($onClauses);
                $clauses->_addClause($whereClauses);
            } elseif (!$onClausesEmpty) {
                $clauses = $onClauses;
            } elseif (!$whereClausesEmpty) {
                $clauses = $whereClauses;
            }

            $clauseIndex = new ClauseMatcher($clauses->toArray(), true);

            switch ($join->getType()) {
                case opal\query\IJoinQuery::INNER:
                    foreach ($rows as $row) {
                        foreach ($sourceData as $joinRow) {
                            if ($clauseIndex->testRowMatch($row, $joinRow)) {
                                $this->_rows[] = array_merge($row, $joinRow);
                            }
                        }
                    }

                    break;

                case opal\query\IJoinQuery::LEFT:
                    foreach ($rows as $row) {
                        $match = false;

                        foreach ($sourceData as $joinRow) {
                            if ($clauseIndex->testRowMatch($row, $joinRow)) {
                                $this->_rows[] = array_merge($row, $joinRow);
                                $match = true;
                            }
                        }

                        if (!$match) {
                            $this->_rows[] = $row;
                        }
                    }

                    break;

                case opal\query\IJoinQuery::RIGHT:
                    foreach ($sourceData as $joinRow) {
                        $match = false;

                        foreach ($rows as $row) {
                            if ($clauseIndex->testRowMatch($row, $joinRow)) {
                                $this->_rows[] = array_merge($row, $joinRow);
                                $match = true;
                            }
                        }

                        if (!$match) {
                            $this->_rows[] = $joinRow;
                        }
                    }

                    break;
            }
        }

        return $this;
    }


    // Normalize
    public function normalizeRows()
    {
        if ($this->_isNormalized || empty($this->_rows)) {
            $this->_isNormalized = true;
            return $this;
        }

        $sourceAlias = $this->_outputManifest->getPrimarySource()->getAlias();

        $rows = $this->_rows;
        $this->_rows = [];

        foreach ($rows as $i => $row) {
            $current = [];

            foreach ($row as $key => $val) {
                $current[$sourceAlias . '.' . $key] = $val;
            }

            $this->_rows[$i] = $current;
        }

        $this->_isNormalized = true;

        return $this;
    }


    // Where
    public function applyWhereClauseList(opal\query\IWhereClauseList $clauseList)
    {
        if (empty($this->_rows)) {
            return $this;
        }

        $this->normalizeRows();
        $maxScore = 1;
        $searchAlias = null;

        if (!$clauseList->isEmpty()) {
            $clauseIndex = new ClauseMatcher($clauseList->toArray());
            $searchFields = null;

            if ($searchController = $this->_outputManifest->getSearchController()) {
                $searchFields = [];
                $searchAlias = $searchController->getAlias();
                $maxWeight = 1;

                foreach ($searchController->getFields() as $fieldName => $field) {
                    $searchFields[$fieldName] = $field['weight'];

                    if ($field['weight'] > $maxWeight) {
                        $maxWeight = $field['weight'];
                    }
                }

                $maxScore = count($searchController->getTerms()) * $maxWeight;
            }

            foreach ($this->_rows as $i => $row) {
                $matchedFields = [];

                if (!$clauseIndex->testRow($row, $matchedFields)) {
                    unset($this->_rows[$i]);
                    continue;
                }

                if ($searchController) {
                    $relevance = 0;

                    foreach ($matchedFields as $fieldName => $matched) {
                        if (!$matched) {
                            continue;
                        }

                        if (isset($searchFields[$fieldName])) {
                            $relevance += $searchFields[$fieldName];
                        }
                    }

                    $this->_rows[$i][$searchAlias] = min($relevance / $maxScore, 1);
                }
            }
        }

        return $this;
    }



    // Groups
    public function applyAggregatesAndGroups(array $groupFields = [])
    {
        if (empty($this->_rows) || (!$this->_outputManifest->hasAggregateFields() && empty($groupFields))) {
            return $this;
        }

        $this->normalizeRows();
        $fields = $this->_outputManifest->getAllFields();
        $aggregateFields = $this->_outputManifest->getAggregateFields();
        $aggregateFieldMap = [];

        foreach ($aggregateFields as $alias => $field) {
            if (!$fields[$alias] instanceof opal\query\ICorrelationField) {
                $aggregateFieldMap[$alias] = $field->getQualifiedName();
            }
        }

        if (empty($aggregateFieldMap)) {
            return $this;
        }

        // init aggregates
        foreach ($this->_rows as &$row) {
            foreach ($aggregateFields as $alias => $field) {
                $qName = $aggregateFieldMap[$alias];

                if (isset($row[$qName])) {
                    $row[$qName] = $row[$qName];
                } else {
                    $row[$qName] = null;
                }
            }

            unset($row);
        }


        // partition
        if (!empty($groupFields)) {
            $rowGroups = [];
            $delim = "\xFF";

            foreach ($this->_rows as $row) {
                $compVal = '';

                foreach ($groupFields as $groupField) {
                    $fieldName = $groupField->getQualifiedName();

                    if (isset($row[$fieldName])) {
                        $compVal .= $row[$fieldName];
                    }

                    $compVal .= $delim;
                }

                $rowGroups[$compVal][] = $row;
            }

            $rowGroups = array_values($rowGroups);
        } else {
            $rowGroups = [$this->_rows];
        }


        // re-list
        $this->_rows = [];

        foreach ($rowGroups as $group) {
            $row = $group[0];

            if (!empty($aggregateFields)) {
                $aggregateData = [];

                foreach ($group as $groupRow) {
                    foreach ($aggregateFieldMap as $alias => $qName) {
                        $aggregateData[$qName][] = $groupRow[$qName];
                    }
                }

                foreach ($aggregateFieldMap as $alias => $qName) {
                    $field = $fields[$alias];

                    if ($field instanceof opal\query\ICorrelationField) {
                        continue;
                    }

                    $rowAggregateData = $aggregateData[$qName];

                    if ($aggregateFields[$alias]->isDistinct()) {
                        $rowAggregateData = array_unique($rowAggregateData);
                    }

                    switch ($field->getType()) {
                        case opal\query\field\Aggregate::TYPE_COUNT:
                            $row[$qName] = count($rowAggregateData);
                            break;

                        case opal\query\field\Aggregate::TYPE_SUM:
                            $row[$qName] = array_sum($rowAggregateData);
                            break;

                        case opal\query\field\Aggregate::TYPE_AVG:
                            $row[$qName] = array_sum($rowAggregateData) / count($rowAggregateData);
                            break;

                        case opal\query\field\Aggregate::TYPE_MIN:
                            $row[$qName] = min($rowAggregateData);
                            break;

                        case opal\query\field\Aggregate::TYPE_MAX:
                            $row[$qName] = max($rowAggregateData);
                            break;

                        case opal\query\field\Aggregate::TYPE_HAS:
                            /** @var array $rowAggregateData */
                            $row[$qName] = !empty($rowAggregateData);
                            break;
                    }
                }
            }

            $this->_rows[] = $row;
        }

        return $this;
    }


    // Having
    public function applyHavingClauseList(opal\query\IHavingClauseList $clauseList)
    {
        if (empty($this->_rows)) {
            return $this;
        }

        $this->normalizeRows();

        if (!$clauseList->isEmpty()) {
            $clauseIndex = new ClauseMatcher($clauseList->toArray());

            foreach ($this->_rows as $i => $row) {
                if (!$clauseIndex->testRow($row)) {
                    unset($this->_rows[$i]);
                }

                unset($row);
            }
        }

        return $this;
    }


    // Distinct
    public function applyDistinct()
    {
        if (empty($this->_rows)) {
            return $this;
        }

        $this->normalizeRows();

        Glitch::incomplete();
    }


    // Order
    public function applyOrderDirectives(array $orderDirectives)
    {
        if (empty($this->_rows) || empty($orderDirectives)) {
            return $this;
        }

        $this->normalizeRows();

        $sortFields = [];
        $sortData = [];
        $sortNullFields = [];
        $sortNullData = [];
        $fieldProcessors = [];

        foreach ($orderDirectives as $directive) {
            if (!$directive instanceof opal\query\IOrderDirective) {
                continue;
            }

            $field = $directive->getField();
            $derefFields = $field->dereference();
            $nullOrder = $directive->getNullOrder();

            foreach ($derefFields as $innerField) {
                $sortFieldName = $innerField->getQualifiedName();

                if (isset($sortFields[$sortFieldName])) {
                    continue;
                }

                if ($directive->isDescending()) {
                    $sortFields[$sortFieldName] = SORT_DESC;
                } else {
                    $sortFields[$sortFieldName] = SORT_ASC;
                }

                if ($nullOrder !== 'ascending') {
                    switch ($nullOrder) {
                        case 'first':
                            $nullOrderDirection = SORT_DESC;
                            break;

                        case 'last':
                            $nullOrderDirection = SORT_ASC;
                            break;

                        case 'descending':
                            $nullOrderDirection = $directive->isDescending() ?
                                SORT_DESC : SORT_ASC;
                            break;

                        default:
                            throw Exceptional::UnexpectedValue(
                                'Unsupported null order key: ' . $nullOrder
                            );
                    }

                    $sortNullFields[$sortFieldName] = $nullOrderDirection;
                    $sortNullData[$sortFieldName] = [];
                }


                $source = $innerField->getSource();
                $adapter = $source->getAdapter();
                $fieldNames = [];

                foreach (opal\schema\Introspector::getFieldProcessors($adapter, [$innerField->getName()]) as $name => $processor) {
                    if (!$processor instanceof opal\query\IFieldValueProcessor) {
                        continue;
                    }

                    $fieldProcessors[$sortFieldName] = $processor;
                }
            }
        }

        foreach ($this->_rows as &$row) {
            foreach ($sortFields as $field => $direction) {
                if (isset($row[$field])) {
                    $value = $row[$field];
                    /** @phpstan-ignore-next-line */
                    $isNull = $row[$field] === null;
                } else {
                    $value = null;
                    $isNull = true;
                }

                if (isset($fieldProcessors[$field])) {
                    $value = $fieldProcessors[$field]->getOrderableValue($value);
                }

                $sortData[$field][] = $value;

                if (isset($sortNullFields[$field])) {
                    $sortNullData[$field][] = $isNull;
                }
            }
        }

        $args = [];

        foreach ($sortFields as $field => $direction) {
            if (isset($sortNullFields[$field])) {
                $args[] = $sortNullData[$field];
                $args[] = $sortNullFields[$field];
            }

            $args[] = &$sortData[$field];
            $args[] = $direction;
        }

        $args[] = &$this->_rows;
        array_multisort(...$args);

        return $this;
    }


    // Limit
    public function applyLimit($limit, $offset)
    {
        if (empty($this->_rows)) {
            return $this;
        }

        $this->normalizeRows();
        $this->_rows = array_slice($this->_rows, (int)$offset, (int)$limit);

        return $this;
    }


    // Populates
    public function applyPopulates(array $populates)
    {
        if (empty($this->_rows) || empty($populates)) {
            return $this;
        }

        return $this->applyAttachments($this->rewritePopulates($populates));
    }

    public function rewritePopulates(array $populates)
    {
        $attachments = [];

        foreach ($populates as $populate) {
            $localAdapter = $populate->getParentSource()->getAdapter();
            $schema = $localAdapter->getQueryAdapterSchema();
            $field = $schema->getField($populate->getFieldName());

            $attachment = $field->rewritePopulateQueryToAttachment($populate);

            if (!$attachment instanceof opal\query\IAttachQuery) {
                throw Exceptional::InvalidArgument(
                    'Cannot populate ' . $populate->getFieldName() . ' - integral schema field cannot convert to attachment'
                );
            }

            if ($attachment instanceof opal\query\IPopulatableQuery) {
                foreach ($populate->getPopulates() as $childPopulate) {
                    $attachment->addPopulate($childPopulate);
                }
            }

            $attachments[$populate->getFieldName()] = $attachment;
        }

        return $attachments;
    }


    // Attachments
    public function applyAttachments(array $attachments)
    {
        if (empty($this->_rows) || empty($attachments)) {
            return $this;
        }

        $this->normalizeRows();

        foreach ($attachments as $attachKey => $attachment) {
            $this->_outputManifest->addOutputField(
                $attachmentQueryField = new opal\query\field\Attachment((string)$attachKey, $attachment)
            );

            $source = $attachment->getSource();
            $adapter = $source->getAdapter();

            $sourceData = $attachment->getSourceManager()->executeQuery($attachment, function ($adapter) use ($attachment) {
                return $adapter->fetchAttachmentData($attachment, $this->_rows);
            });

            $manipulator = new self($source, $sourceData, true);

            if ($attachment instanceof opal\query\ISelectAttachQuery && $attachment->isPopulate()) {
                $manipulator->_outputManifest->queryRequiresPartial(true);
            }

            $clauseList = $attachment->getJoinClauseList()->toArray();
            $clauseIndex = new ClauseMatcher($clauseList, true);
            $isFetchQuery = $attachment instanceof opal\query\IFetchQuery;
            $isValueAttachment = $attachment->getType() === 0 || $attachment->getType() === 3;


            // Get key / val fields
            $keyField = $valField = null;

            if ($attachment instanceof opal\query\ISelectAttachQuery) {
                $keyField = $attachment->getListKeyField();
                $valField = $attachment->getListValueField();
            }


            // Import sources to child manipulator
            if ($attachment instanceof opal\query\IJoinProviderQuery) {
                foreach ($attachment->getJoins() as $join) {
                    $manipulator->getOutputManifest()->importSource($join->getSource());
                }
            }


            // Prepare groups & having clauses
            $groups = [];
            $havingClauseList = null;

            if ($isReadQuery = $attachment instanceof opal\query\IReadQuery) {
                if ($attachment instanceof opal\query\IGroupableQuery) {
                    $groups = $attachment->getGroupFields();
                }

                if ($attachment instanceof opal\query\IHavingClauseQuery && $attachment->hasHavingClauses()) {
                    $havingClauseList = $attachment->getHavingClauseList();
                }
            }


            // Prepare order directives
            $orderDirectives = $attachment instanceof opal\query\IOrderableQuery ?
                $attachment->getOrderDirectives() : null;


            // Prepare limit & offset
            $limit = $offset = $canLimit = null;

            if ($attachment instanceof opal\query\ILimitableQuery) {
                $limit = $attachment->getLimit();

                if ($attachment instanceof opal\query\IOffsettableQuery) {
                    $offset = $attachment->getOffset();
                }

                if ($isValueAttachment) {
                    $limit = 1;
                }

                $canLimit = $limit !== null || $offset !== null;
            }


            $index = [];
            $dataSet = [];


            // Iterate data
            foreach ($this->_rows as $i => $row) {
                // Filter source data
                if (empty($clauseList)) {
                    $attachData = $sourceData;
                } else {
                    $attachData = [];

                    foreach ($sourceData as $joinRow) {
                        if ($clauseIndex->testRowMatch($row, $joinRow)) {
                            $attachData[] = $joinRow;
                        }
                    }
                }

                $manipulator->setRows($attachData);

                // Groups, aggregates and having clauses
                if ($isReadQuery) {
                    $manipulator->applyAggregatesAndGroups($groups);

                    if ($havingClauseList !== null) {
                        $manipulator->applyHavingClauseList($havingClauseList);
                    }
                }


                // Distinct
                if ($attachment instanceof opal\query\IDistinctQuery && $attachment->isDistinct()) {
                    $manipulator->applyDistinct();
                }


                // Order directives
                if (!empty($orderDirectives)) {
                    $manipulator->applyOrderDirectives($orderDirectives);
                }


                // Limit & offset
                if ($canLimit) {
                    $manipulator->applyLimit($limit, $offset);
                }

                $setCount = count($dataSet);

                foreach ($manipulator->getRows() as $dataSetRow) {
                    $delta = ++$setCount;
                    $index[$i][$delta] = $delta;
                    $dataSet[$delta] = $dataSetRow;
                }
            }

            $manipulator->setRows($dataSet);


            // Child attachments
            $childAttachments = [];

            if ($attachment instanceof opal\query\IPopulatableQuery) {
                $childAttachments = array_merge($childAttachments, $this->rewritePopulates($attachment->getPopulates()));
            }

            if ($attachment instanceof opal\query\IAttachProviderQuery) {
                $childAttachments = array_merge($childAttachments, $attachment->getAttachments());
            }

            if (!empty($childAttachments)) {
                $manipulator->applyAttachments($childAttachments);
            }

            if ($attachment instanceof opal\query\ICombinableQuery) {
                $manipulator->applyCombines($attachment->getCombines());
            }

            $nestFields = null;

            if ($attachment instanceof opal\query\INestableQuery) {
                $nestFields = $attachment->getNestFields();
            }


            $dataSet = $manipulator->getRows();
            $qName = $attachmentQueryField->getQualifiedName();


            // Format and apply output
            foreach ($this->_rows as $i => &$row) {
                $attachData = [];

                if (isset($index[$i])) {
                    foreach ($index[$i] as $delta) {
                        $attachData[] = $dataSet[$delta];
                    }
                }

                $attachData = $manipulator
                    ->setRows($attachData)
                    ->applyOutputFields($keyField, $valField, $nestFields, $isFetchQuery)
                    ->getRows();

                if ($isValueAttachment) {
                    $attachData = array_shift($attachData);
                }

                $row[$qName] = $attachData;
            }

            unset($row);
        }

        return $this;
    }



    // Combines
    public function applyCombines(array $combines)
    {
        if (empty($combines)) {
            return $this;
        }


        foreach ($combines as $name => $combine) {
            $this->_outputManifest->addOutputField(new opal\query\field\Combine((string)$name, $combine));
        }

        return $this;
    }


    // Output
    public function applyOutputFields(opal\query\IField $keyField = null, opal\query\IField $valField = null, array $nestFields = null, $forFetch = false, callable $formatter = null)
    {
        if (empty($this->_rows)) {
            return $this;
        }

        $this->normalizeRows();

        $temp = $this->_rows;
        $this->_rows = [];


        // Prepare helpers
        $primarySource = $this->_outputManifest->getPrimarySource();
        $primaryAdapter = $primarySource->getAdapter();
        $outputFields = $this->_outputManifest->getOutputFields();
        $aggregateFields = $this->_outputManifest->getAggregateFields();
        $wildcards = $this->_outputManifest->getWildcardMap();
        $fieldProcessors = $this->_outputManifest->getOutputFieldProcessors();
        $combines = $this->_outputManifest->getCombines();
        $requiresPartial = $this->_outputManifest->requiresPartial($forFetch);

        // Prepare qualified names
        $qNameMap = [];
        $overrides = [];

        foreach ($outputFields as $alias => $field) {
            $qName = $field->getLogicalAlias() ?? $field->getQualifiedName();

            if ($field instanceof opal\query\IVirtualField && !isset($fieldProcessors[$qName])) {
                $fields = $field->dereference();
                $qNameMap[$alias] = [];

                foreach ($fields as $key => $innerField) {
                    $qNameMap[$alias][$innerField->getName()] = $innerField->getQualifiedName();
                }
            } else {
                $qNameMap[$alias] = $qName;
            }

            $lastField = $field;

            while (($field = $field->getOverrideField()) && ($field !== $lastField)) {
                $overrides[$alias][] = $field->getQualifiedName();
                $lastField = $field;
            }
        }


        // Prepare key / val field
        $oldValField = $keyName = $valQName = null;
        $outputPrimaryKeySet = false;
        $keyNameList = $valName = null;

        if ($keyField) {
            $keyName = $keyField->getQualifiedName();

            if ($keyField instanceof opal\query\IVirtualField) {
                $keyNameList = [];

                foreach ($keyField->dereference() as $derefKeyField) {
                    $keyNameList[] = $derefKeyField->getQualifiedName();
                }

                if (count($keyNameList) == 1) {
                    $keyName = array_shift($keyNameList);
                }
            }
        }

        if ($valField) {
            if ($valField instanceof opal\query\IVirtualField) {
                $derefFields = $valField->dereference();
                $oldValField = $valField;

                if (count($derefFields) > 1) {
                    if ($valField->getName() == '@primary') {
                        $outputPrimaryKeySet = true;
                        $outputFields = [];

                        foreach ($derefFields as $field) {
                            $outputFields[$field->getName()] = $field;
                        }
                    }

                    //Glitch::incomplete(['multi primary deref', $outputFields]);
                    $valQName = $valField->getQualifiedName();
                    $valName = $valField->getName();
                } else {
                    $valQName = $valField->getQualifiedName();

                    if (!isset($fieldProcessors[$valQName])) {
                        $valField = array_shift($derefFields);
                        $valQName = $valField->getQualifiedName();
                    }

                    $valName = $valField->getName();
                }
            } else {
                $valQName = $valField->getQualifiedName();
                $valName = $valField->getLogicalAlias() ?? $valField->getName();
            }

            $nestFields = null;
        } else {
            if (empty($nestFields)) {
                $nestFields = null;
            } else {
                $tempFields = $nestFields;
                $nestFields = [];

                foreach ($tempFields as $field) {
                    $nestFields[$field->getQualifiedName()] = $field;
                }
            }
        }



        // Prepare object field
        $objectKey = $primarySource->getAlias() . '.@object';
        $fetchObject = false;

        if ($forFetch) {
            $fetchObject = isset($temp[0][$objectKey]);
        }


        // Iterate data
        foreach ($temp as $row) {
            $record = $partial = null;

            if ($requiresPartial) {
                $partial = $primaryAdapter->newPartial();
            }

            if ($forFetch && !$requiresPartial) {
                $record = $primaryAdapter->newRecord();
            }

            // Pre-process row
            if (!empty($fieldProcessors)) {
                foreach ($fieldProcessors as $qName => $fieldProcessor) {
                    $row[$qName] = $fieldProcessor->inflateValueFromRow(
                        $qName,
                        $row,
                        $record
                    );
                }
            }


            // Single value output
            if ($valQName) {
                if (array_key_exists($valQName, $row)) {
                    $current = $row[$valQName];
                } elseif ($outputPrimaryKeySet) {
                    $primaryValues = [];

                    foreach ($outputFields as $outputField) {
                        $innerQName = $outputField->getQualifiedName();
                        $innerName = $outputField->getName();

                        $primaryValues[$innerName] = $row[$innerQName] ?? null;
                    }

                    $current = new opal\record\PrimaryKeySet(array_keys($primaryValues), $primaryValues);
                } elseif (isset($row[$valName])) {
                    $current = $row[$valName];
                } else {
                    $current = null;
                }

                // Entity object
            } elseif ($fetchObject) {
                if (isset($row[$objectKey])) {
                    $current = $row[$objectKey];
                } else {
                    $current = null;
                }


                // Normal row
            } else {
                $current = [];

                // Apply wildcards
                if (!empty($wildcards)) {
                    $mergedMuteFields = [];

                    foreach ($wildcards as $sourceAlias => $muteFields) {
                        $mergedMuteFields = array_merge($mergedMuteFields, $muteFields);
                    }

                    foreach ($row as $key => $value) {
                        if (isset($aggregateFields[$key])) {
                            continue;
                        }

                        $parts = explode('.', $key, 2);
                        $fieldName = (string)array_pop($parts);
                        $s = (string)array_shift($parts);

                        if (substr($fieldName, 0, 1) == '@') {
                            continue;
                        }

                        if (empty($s)) {
                            if (array_key_exists($fieldName, $mergedMuteFields)) {
                                if (null === $mergedMuteFields[$fieldName]) {
                                    continue;
                                } else {
                                    $fieldName = $mergedMuteFields[$fieldName];
                                }
                            }
                        } elseif (!isset($wildcards[$s])) {
                            continue;
                        } else {
                            if (array_key_exists($fieldName, $wildcards[$s])) {
                                if (null === $wildcards[$s][$fieldName]) {
                                    continue;
                                } else {
                                    $fieldName = $wildcards[$s][$fieldName];
                                }
                            }
                        }

                        $current[$fieldName] = $value;
                    }
                }


                // Add known fields
                foreach ($qNameMap as $alias => $qName) {
                    // Multi key set
                    if (is_array($qName)) {
                        $qValue = [];

                        foreach ($qName as $innerName => $innerQName) {
                            if (isset($row[$innerQName])) {
                                $qValue[$innerName] = $row[$innerQName];
                            } else {
                                $qValue[$innerName] = null;
                            }
                        }

                        $current[$alias] = new opal\record\PrimaryKeySet(array_keys($qValue), $qValue);
                        continue;
                    }

                    // Single value
                    $qValue = null;

                    if (isset($row[$qName])) {
                        $qValue = $row[$qName];
                    } elseif (isset($overrides[$alias])) {
                        foreach ($overrides[$alias] as $overrideQName) {
                            if (isset($row[$overrideQName])) {
                                $qValue = $row[$overrideQName];
                                break;
                            }
                        }
                    }


                    if (isset($aggregateFields[$alias])) {
                        $qValue = $aggregateFields[$alias]->normalizeOutputValue($qValue);
                    }

                    if ($qValue !== null || !isset($current[$alias])) {
                        $current[$alias] = $qValue;
                    }
                }

                // Combines
                foreach ($combines as $combineName => $combine) {
                    $combineFields = $combine->getFields();
                    $nullFields = $combine->getNullFields();
                    $combineRow = [];
                    $isNull = false;

                    foreach ($combineFields as $combineFieldName => $combineField) {
                        if (!$isNull) {
                            $targetField = $combineField->getTargetFields()[0];
                            $targetQName = $targetField->getQualifiedName();

                            if (isset($row[$targetQName])) {
                                $combineRow[$combineFieldName] = $row[$targetQName];
                            } else {
                                $combineRow[$combineFieldName] = null;
                            }

                            if ($combineRow[$combineFieldName] === null && isset($nullFields[$combineFieldName])) {
                                $combineRow = null;
                                $isNull = true;
                            }
                        }

                        if (!$combine->isCopy()) {
                            unset($current[$combineField->getName()]);
                        }
                    }

                    $current[$combineName] = $combineRow;
                }


                // Convert to record object
                if ($partial) {
                    $record = $partial;
                }

                if ($record) {
                    $current = $record->populateWithPreparedData($current);
                }
            }


            // Add row to output
            $key = null;

            if ($keyName) {
                if (isset($row[$keyName])) {
                    $key = $row[$keyName];
                } elseif ($keyField && isset($row[$t = $keyField->getAlias()])) {
                    $key = $row[$t];
                    $keyName = $t;
                } elseif ($keyNameList !== null) {
                    $key = [];

                    foreach ($keyNameList as $derefKeyName) {
                        if (isset($row[$derefKeyName])) {
                            $key[] = $row[$derefKeyName];
                        }
                    }

                    $key = implode('/', $key);
                }
            }

            if ($key !== null && !is_scalar($key)) {
                $key = (string)$key;
            }

            if ($formatter) {
                $current = $formatter($current, $key);
            }

            if ($nestFields) {
                $nestValues = [];

                foreach ($nestFields as $nestQName => $nestField) {
                    if (isset($row[$nestQName])) {
                        $nestValues[$nestQName] = $row[$nestQName];
                    } elseif (isset($row[$t = $nestField->getAlias()])) {
                        $nestValues[$nestQName] = $row[$t];
                    } else {
                        $nestValues[$nestQName] = null;
                    }
                }

                if ($key) {
                    $current = [$key => $current];
                } else {
                    $current = [$current];
                }

                while (!empty($nestValues)) {
                    $current = [array_pop($nestValues) => $current];
                }

                $this->_rows = array_merge_recursive($this->_rows, $current);
            } elseif ($key) {
                $this->_rows[$key] = $current;
            } else {
                $this->_rows[] = $current;
            }
        }

        return $this;
    }
}
