<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use DecodeLabs\Glitch\Dumpable;

use df\opal;

class Fetch implements IFetchQuery, Dumpable
{
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Locational;
    use TQuery_Correlatable;
    use TQuery_JoinConstrainable;
    use TQuery_RelationAttachable;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_WhereClauseFactory;
    use TQuery_Searchable;
    use TQuery_Orderable;
    use TQuery_Nestable;
    use TQuery_Limitable;
    use TQuery_Offsettable;
    use TQuery_Populatable;
    use TQuery_Pageable;
    use TQuery_Read;

    public function __construct(ISourceManager $sourceManager, ISource $source)
    {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
    }

    public function getQueryType()
    {
        return IQueryTypes::FETCH;
    }



    // Output
    public function count(): int
    {
        return $this->_sourceManager->executeQuery($this, function ($adapter) {
            return (int)$adapter->countFetchQuery($this);
        });
    }

    protected function _fetchSourceData($keyField = null, $valField = null)
    {
        if ($keyField !== null) {
            $keyField = $this->_sourceManager->extrapolateDataField($this->_source, $keyField);
        }

        $formatter = null;

        if (is_callable($valField)) {
            $formatter = $valField;
            $valField = null;
        }

        $output = $this->_sourceManager->executeQuery($this, function ($adapter) {
            return $adapter->executeFetchQuery($this);
        });

        $output = $this->_createBatchIterator($output, $keyField, null, true, $formatter);

        if ($this->_paginator && $this->_offset == 0 && $this->_limit) {
            $count = count($output);

            if ($count < $this->_limit) {
                $this->_paginator->setTotal($count);
            }
        }

        return $output;
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

        if (!empty($this->_joins)) {
            yield 'property:*joins' => $this->_joins;
        }

        if ($this->hasWhereClauses()) {
            yield 'property:*where' => $this->getWhereClauseList();
        }

        if ($this->_searchController) {
            yield 'property:*search' => $this->_searchController;
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


class Fetch_Attach extends Fetch implements IFetchAttachQuery
{
    use TQuery_Attachment;
    use TQuery_ParentAwareJoinClauseFactory;

    public function __construct(IQuery $parent, ISourceManager $sourceManager, ISource $source)
    {
        $this->_parent = $parent;
        parent::__construct($sourceManager, $source);

        $this->_joinClauseList = new opal\query\clause\JoinList($this);
    }

    public function getQueryType()
    {
        return IQueryTypes::FETCH_ATTACH;
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
