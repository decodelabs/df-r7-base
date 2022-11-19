<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;

class Select implements ISelectQuery, Dumpable
{
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Derivable;
    use TQuery_Locational;
    use TQuery_Distinct;
    use TQuery_Correlatable;
    use TQuery_Joinable;
    use TQuery_Attachable;
    use TQuery_Populatable;
    use TQuery_Combinable;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_WhereClauseFactory;
    use TQuery_Searchable;
    use TQuery_Groupable;
    use TQuery_HavingClauseFactory;
    use TQuery_Orderable;
    use TQuery_Nestable;
    use TQuery_Limitable;
    use TQuery_Offsettable;
    use TQuery_Pageable;
    use TQuery_Read;
    use TQuery_SelectSourceDataFetcher;

    public function __construct(ISourceManager $sourceManager, ISource $source)
    {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
    }

    public function getQueryType()
    {
        return IQueryTypes::SELECT;
    }


    // Sources
    public function addOutputFields(string ...$fields)
    {
        foreach ($fields as $field) {
            $this->_sourceManager->extrapolateOutputField($this->_source, $field);
        }

        return $this;
    }




    // Output
    public function count(): int
    {
        return $this->_sourceManager->executeQuery($this, function ($adapter) {
            return (int)$adapter->countSelectQuery($this);
        });
    }

    public function toList($valField1, $valField2 = null)
    {
        if ($valField2 !== null) {
            $keyField = $valField1;
            $valField = $valField2;
        } else {
            $keyField = null;
            $valField = $valField1;
        }

        $data = $this->_fetchSourceData($keyField, $valField);

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

    public function toValue($valField = null)
    {
        if ($valField !== null) {
            $valField = $this->_sourceManager->extrapolateDataField($this->_source, $valField);
            $data = $this->toRow();

            $key = $valField->getAlias();

            if (isset($data[$key])) {
                return $data[$key];
            } else {
                return null;
            }
        } else {
            if (null !== ($data = $this->toRow())) {
                return array_shift($data);
            }

            return null;
        }
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*sources' => $this->_sourceManager,
            '*fields' => $this->_source
        ];

        if (!empty($this->_populates)) {
            yield 'property:*populates' => $this->_populates;
        }

        if (!empty($this->_combines)) {
            yield 'property:*combines' => $this->_combines;
        }

        if (!empty($this->_joins)) {
            yield 'property:*join' => $this->_joins;
        }

        if (!empty($this->_attachments)) {
            yield 'property:*attach' => $this->_attachments;
        }

        if ($this->hasWhereClauses()) {
            yield 'property:*where' => $this->getWhereClauseList();
        }

        if ($this->_searchController) {
            yield 'property:*search' => $this->_searchController;
        }

        if (!empty($this->_group)) {
            yield 'property:*group' => $this->_groups;
        }

        if ($this->hasHavingClauses()) {
            yield 'property:*having' => $this->_havingClauseList;
        }

        if (!empty($this->_order)) {
            $order = [];

            foreach ($this->_order as $directive) {
                $order[] = $directive->toString();
            }

            yield 'property:*order' => implode(', ', $order);
        }

        if (!empty($this->_nest)) {
            yield 'property:*nest' => $this->_nest;
        }

        if ($this->_limit) {
            yield 'property:*limit' => $this->_limit;
        }

        if ($this->_offset) {
            yield 'property:*offset' => $this->_offset;
        }

        if ($this->_paginator) {
            yield 'property:*paginator' => $this->_paginator;
        }
    }
}


## ATTACH
class Select_Attach extends Select implements ISelectAttachQuery
{
    use TQuery_Attachment;
    use TQuery_AttachmentListExtension;
    use TQuery_AttachmentValueExtension;
    use TQuery_ParentAwareJoinClauseFactory;

    public function __construct(IQuery $parent, ISourceManager $sourceManager, ISource $source)
    {
        $this->_parent = $parent;
        parent::__construct($sourceManager, $source);
    }

    public function getQueryType()
    {
        return IQueryTypes::SELECT_ATTACH;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*type' => self::typeIdToName($this->_type),
            '*on' => $this->_joinClauseList,
        ];

        yield from parent::glitchDump();
    }
}


## UNION
class Select_Union extends Select implements IUnionSelectQuery
{
    protected $_union;
    protected $_isUnionDistinct = true;

    public function __construct(IUnionQuery $union, ISource $source)
    {
        $this->_union = $union;
        parent::__construct($union->getSourceManager(), $source);
    }

    public function isUnionDistinct(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isUnionDistinct = $flag;
            return $this;
        }

        return $this->_isUnionDistinct;
    }

    public function endSelect()
    {
        $this->_union->addQuery($this);
        return $this->_union;
    }

    public function with(...$fields)
    {
        $this->endSelect();

        return Initiator::factory()
            ->beginUnionSelect($this->_union, $fields, true);
    }

    public function withAll(...$fields)
    {
        $this->endSelect();

        return Initiator::factory()
            ->beginUnionSelect($this->_union, $fields, false);
    }
}
