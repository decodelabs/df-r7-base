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

class Select implements ISelectQuery, Inspectable
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
    public function count()
    {
        return $this->_sourceManager->executeQuery($this, function ($adapter) {
            return (int)$adapter->countSelectQuery($this);
        });
    }

    public function toList($valField1, $valField2=null)
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
            throw new UnexpectedValueException(
                'Source did not return a result that could be converted to an array'
            );
        }

        return $data;
    }

    public function toValue($valField=null)
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

        if (!empty($this->_combines)) {
            $entity->setProperty('*combines', $inspector($this->_combines));
        }

        if (!empty($this->_joins)) {
            $entity->setProperty('*join', $inspector($this->_joins));
        }

        if (!empty($this->_attachments)) {
            $entity->setProperty('*attach', $inspector($this->_attachments));
        }

        if ($this->hasWhereClauses()) {
            $entity->setProperty('*where', $inspector($this->getWhereClauseList()));
        }

        if ($this->_searchController) {
            $entity->setProperty('*search', $inspector($this->_searchController));
        }

        if (!empty($this->_group)) {
            $entity->setProperty('*group', $inspector($this->_groups));
        }

        if ($this->hasHavingClauses()) {
            $entity->setProperty('*having', $inspector($this->_havingClauseList));
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

    public function isUnionDistinct(bool $flag=null)
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
