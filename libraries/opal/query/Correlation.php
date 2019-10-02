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

class Correlation implements ICorrelationQuery, Inspectable
{
    use TQuery;
    use TQuery_ParentAware;
    use TQuery_ParentAwareJoinClauseFactory;
    use TQuery_NestedComponent;
    use TQuery_JoinConstrainable;
    use TQuery_WhereClauseFactory;
    use TQuery_Groupable;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Offsettable;

    protected $_source;
    protected $_sourceManager;
    protected $_fieldAlias;
    protected $_applicator;

    public function __construct(ISourceProvider $parent, ISourceManager $sourceManager, ISource $source, $fieldAlias=null)
    {
        $this->_parent = $parent;
        $this->_source = $source;
        $this->_sourceManager = $sourceManager;
        $this->_fieldAlias = $fieldAlias;

        if ($this->_fieldAlias === null) {
            $field = $this->_source->getLastOutputDataField();
            $this->_fieldAlias = $field->getAlias();
        }
    }

    public function getQueryType()
    {
        return IQueryTypes::CORRELATION;
    }

    public function __clone()
    {
        if ($this->_joinClauseList) {
            $this->_joinClauseList = clone $this->_joinClauseList;
        }

        if ($this->_whereClauseList) {
            $this->_whereClauseList = clone $this->_whereClauseList;
        }
    }


    // Sources
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

    // Correlation
    public function getFieldAlias()
    {
        return $this->_fieldAlias;
    }


    // Applicator
    public function setApplicator(callable $applicator=null)
    {
        $this->_applicator = $applicator;
        return $this;
    }

    public function getApplicator()
    {
        return $this->_applicator;
    }

    public function endCorrelation($fieldAlias=null)
    {
        if ($fieldAlias !== null) {
            $this->_fieldAlias = $fieldAlias;
        }

        if ($this->_applicator) {
            call_user_func_array($this->_applicator, [$this]);
        } elseif ($this->_parent instanceof ICorrelatableQuery) {
            $this->_parent->addCorrelation($this);
        }

        return $this->getNestedParent();
    }

    public function getCorrelationSource()
    {
        $parent = $this->_parent;

        while ($parent instanceof IParentQueryAware) {
            $parent = $parent->getParentQuery();
        }

        return $parent->getSource();
    }

    public function getCorrelatedClauses(ISource $correlationSource=null)
    {
        if ($correlationSource === null) {
            $correlationSource = $this->getCorrelationSource();
        }

        if ($this->_joinClauseList) {
            $clauses = $this->_joinClauseList->extractClausesFor($correlationSource);
        } else {
            $clauses = [];
        }

        if ($this->_whereClauseList) {
            $clauses = array_merge(
                $clauses, $this->_whereClauseList->extractClausesFor($correlationSource)
            );
        }

        return $clauses;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties([
            '*fieldAlias' => $inspector($this->_fieldAlias),
            '*fields' => $inspector($this->_source),
            '*on' => $inspector($this->_joinClauseList)
        ]);

        if (!empty($this->_joins)) {
            $entity->setProperty('*joins', $inspector($this->_joins));
        }

        if ($this->_whereClauseList && !$this->_whereClauseList->isEmpty()) {
            $entity->setProperty('*where', $inspector($this->_whereClauseList));
        }

        if (!empty($this->_group)) {
            $entity->setProperty('*group', $inspector($this->_groups));
        }

        if ($this->_limit) {
            $entity->setProperty('*limit', $inspector($this->_limit));
        }

        if ($this->_offset) {
            $entity->setProperty('*offset', $inspector($this->_offset));
        }
    }
}
