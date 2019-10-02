<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Fetch implements IFetchQuery, Inspectable
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
    public function count()
    {
        return $this->_sourceManager->executeQuery($this, function ($adapter) {
            return (int)$adapter->countFetchQuery($this);
        });
    }

    protected function _fetchSourceData($keyField=null, $valField=null)
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
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties([
            '*sources' => $inspector($this->_sourceManager),
            '*fields' => $inspector($this->_source)
        ]);

        if (!empty($this->_populates)) {
            $entity->setProperty('*populates', $inspector($this->_populates));
        }

        if (!empty($this->_joins)) {
            $entity->setProperty('*joins', $inspector($this->_joins));
        }

        if ($this->hasWhereClauses()) {
            $entity->setProperty('*where', $inspector($this->getWhereClauseList()));
        }

        if ($this->_searchController) {
            $entity->setProperty('*search', $inspector($this->_searchController));
        }

        if (!empty($this->_order)) {
            $order = [];

            foreach ($this->_order as $directive) {
                $order[] = $directive->toString();
            }

            $entity->setProperty('*order', $inspector(implode(', ', $order)));
        }

        if (!empty($this->_nest)) {
            $entity->setProperty('*nest', $inspector($this->_nest));
        }

        if ($this->_limit) {
            $entity->setProperty('*limit', $inspector($this->_limit));
        }

        if ($this->_offset) {
            $entity->setProperty('*offset', $inspector($this->_offset));
        }

        if ($this->_paginator) {
            $entity->setProperty('*paginator', $inspector($this->_paginator));
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
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties([
            '*type' => $inspector(self::typeIdToName($this->_type)),
            '*on' => $inspector($this->_joinClauseList),
        ]);

        parent::glitchInspect($entity, $inspector);
    }
}
