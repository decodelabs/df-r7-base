<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;

class Populate implements IPopulateQuery, Dumpable
{
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Populate;
    use TQuery_Populatable;
    use TQuery_WhereClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Offsettable;


    public static function typeIdToName($id)
    {
        switch ($id) {
            case IPopulateQuery::TYPE_SOME:
                return 'SOME';

            default:
            case IPopulateQuery::TYPE_ALL:
                return 'ALL';
        }
    }

    public function __construct(IPopulatableQuery $parent, $fieldName, $type, array $selectFields=null)
    {
        $this->_parent = $parent;
        $this->_type = $type;

        $parentSourceManager = $parent->getSourceManager();

        $this->_field = $parentSourceManager->extrapolateIntrinsicField($parent->getSource(), $fieldName);
        $intrinsicFieldName = $this->_field->getName();

        $adapter = $this->_field->getSource()->getAdapter();

        if (!$adapter instanceof opal\query\IIntegralAdapter) {
            throw Glitch::ELogic(
                'Cannot populate field '.$fieldName.' - adapter is not integral'
            );
        }

        $this->_sourceManager = new SourceManager($parentSourceManager->getTransaction());
        $this->_sourceManager->setParentSourceManager($parentSourceManager);

        $schema = $adapter->getQueryAdapterSchema();
        $field = $schema->getField($intrinsicFieldName);

        if (!$field instanceof opal\schema\IRelationField) {
            throw Glitch::ERuntime(
                'Cannot populate '.$intrinsicFieldName.' - field is not a relation'
            );
        }

        $adapter = $field->getTargetQueryAdapter();
        $alias = uniqid('ppl_'.$intrinsicFieldName);

        if (empty($selectFields)) {
            $selectFields = ['*'];
        }


        $this->_source = $this->_sourceManager->newSource($adapter, $alias, $selectFields);
    }

    public function getParentQuery()
    {
        return $this->_parent;
    }

    public function getParentSource()
    {
        return $this->_field->getSource();
    }

    public function getParentSourceAlias()
    {
        return $this->_field->getSource()->getAlias();
    }


    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*parent' => $this->_parent->getSource()->getId(),
            '*field' => $this->_field,
            '*type' => self::typeIdToName($this->_type)
        ];

        if (!empty($this->_populates)) {
            yield 'property:*populates' => $this->_populates;
        }

        if ($this->_whereClauseList && !$this->_whereClauseList->isEmpty()) {
            yield 'property:*where' => $this->_whereClauseList;
        }

        if (!empty($this->_order)) {
            $order = [];

            foreach ($this->_order as $directive) {
                $order[] = $directive->toString();
            }

            yield 'property:*order' => implode(', ', $order);
        }

        if ($this->_limit) {
            yield 'property:*limit' => $this->_limit;
        }

        if ($this->_offset) {
            yield 'property:*offset' => $this->_offset;
        }
    }
}
