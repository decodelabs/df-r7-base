<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\query;

use DecodeLabs\Exceptional;
use df\core;

use df\opal;

class Initiator implements IInitiator
{
    use TQuery_TransactionAware;

    protected $_mode = null;
    protected $_fieldMap = [];
    protected $_data = null;
    protected $_joinType = null;
    protected $_parentQuery = null;
    protected $_distinct = false;
    protected $_union = null;
    protected $_isUnionDistinct = false;
    protected $_derivationParentInitiator;
    protected $_applicator;

    public static function modeIdToName($id)
    {
        switch ($id) {
            case IQueryTypes::SELECT: return 'SELECT';
            case IQueryTypes::FETCH: return 'FETCH';
            case IQueryTypes::INSERT: return 'INSERT';
            case IQueryTypes::BATCH_INSERT: return 'BATCH_INSERT';
            case IQueryTypes::REPLACE: return 'REPLACE';
            case IQueryTypes::BATCH_REPLACE: return 'BATCH_REPLACE';
            case IQueryTypes::UPDATE: return 'UPDATE';
            case IQueryTypes::DELETE: return 'DELETE';

            case IQueryTypes::CORRELATION: return 'CORRELATION';
            case IQueryTypes::POPULATE: return 'POPULATE';

            case IQueryTypes::JOIN: return 'JOIN';
            case IQueryTypes::JOIN_CONSTRAINT: return 'JOIN_CONSTRAINT';
            case IQueryTypes::REMOTE_JOIN: return 'REMOTE_JOIN';

            case IQueryTypes::SELECT_ATTACH: return 'SELECT_ATTACH';
            case IQueryTypes::FETCH_ATTACH: return 'FETCH_ATTACH';
            case IQueryTypes::REMOTE_ATTACH: return 'REMOVE_ATTACH';

            default: return '*uninitialized*';
        }
    }

    public static function factory()
    {
        return new self();
    }

    public function setApplicator(callable $applicator = null)
    {
        $this->_applicator = $applicator;
        return $this;
    }

    public function getApplicator()
    {
        return $this->_applicator;
    }


    // Select
    public function beginSelect(array $fields = [], $distinct = false)
    {
        $this->_setMode(IQueryTypes::SELECT);
        $fields = core\collection\Util::flatten($fields, false);

        if (empty($fields) || (count($fields) == 1 && $fields[0] === null)) {
            $fields = ['*'];
        }

        $this->_fieldMap = $fields;
        $this->_distinct = (bool)$distinct;

        return $this;
    }

    // Union
    public function beginUnion()
    {
        $this->_setMode(IQueryTypes::UNION);
        $sourceManager = new opal\query\SourceManager($this->_transaction);
        return new Union($sourceManager);
    }

    public function beginUnionSelect(IUnionQuery $union, array $fields = [], $unionDistinct = true, $selectDistinct = false)
    {
        $this->beginSelect($fields, $selectDistinct);
        $this->_mode = IQueryTypes::UNION;
        $this->_union = $union;
        $this->_isUnionDistinct = (bool)$unionDistinct;
        return $this;
    }


    // Fetch
    public function beginFetch()
    {
        $this->_setMode(IQueryTypes::FETCH);
        $this->_fieldMap = ['*' => null];

        return $this;
    }


    // Insert
    public function beginInsert($row)
    {
        $this->_setMode(IQueryTypes::INSERT);
        $this->_fieldMap = ['*' => null];
        $this->_data = $row;

        return $this;
    }


    // Batch insert
    public function beginBatchInsert($rows = [])
    {
        $this->_setMode(IQueryTypes::BATCH_INSERT);
        $this->_fieldMap = ['*' => null];
        $this->_data = $rows;

        return $this;
    }


    // Replace
    public function beginReplace($row)
    {
        $this->_setMode(IQueryTypes::REPLACE);
        $this->_fieldMap = ['*' => null];
        $this->_data = $row;

        return $this;
    }

    // Batch replace
    public function beginBatchReplace($rows = [])
    {
        $this->_setMode(IQueryTypes::BATCH_REPLACE);
        $this->_fieldMap = ['*' => null];
        $this->_data = $rows;

        return $this;
    }

    // Update
    public function beginUpdate(array $valueMap = null)
    {
        $this->_setMode(IQueryTypes::UPDATE);
        $this->_data = $valueMap;

        if (is_array($valueMap)) {
            $this->_fieldMap = $valueMap;
        }

        return $this;
    }

    // Delete
    public function beginDelete()
    {
        $this->_setMode(IQueryTypes::DELETE);
        $this->_fieldMap = ['*' => null];

        return $this;
    }




    // Correlation
    public function beginCorrelation(ISourceProvider $parent, $field, $alias = null)
    {
        $this->_setMode(IQueryTypes::CORRELATION);
        $this->_parentQuery = $parent;
        $this->_fieldMap = [$field => $alias];

        return $this;
    }


    // Populate
    public function beginPopulate(IPopulatableQuery $parent, array $fields, $type = IPopulateQuery::TYPE_ALL, array $selectFields = null)
    {
        $this->_setMode(IQueryTypes::POPULATE);
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flatten($fields);
        $isAll = false;

        if ($selectFields) {
            $selectFields = core\collection\Util::flatten($selectFields);
        }

        switch ($type) {
            case IPopulateQuery::TYPE_ALL:
                $isAll = true;

                // no break
            case IPopulateQuery::TYPE_SOME:
                $this->_joinType = $type;
                break;

            default:
                throw Exceptional::InvalidArgument(
                    $type . ' is not a valid populate type'
                );
        }

        if (
            $this->_joinType == IPopulateQuery::TYPE_SOME &&
            count($fields) != 1
        ) {
            throw Exceptional::InvalidArgument(
                'populateSome() can only handle one field at a time'
            );
        }

        $populate = null;

        foreach ($fields as $field) {
            $children = [];

            if ($field instanceof IField) {
                $field = $field->getName();
            }

            if (false !== strpos($field, '.')) {
                $children = explode('.', $field);
                $field = array_shift($children);
            }

            if (!$populate = $parent->getPopulate($field)) {
                $populate = new Populate($parent, $field, $type, $selectFields);
            }

            if (!empty($children)) {
                foreach ($children as $child) {
                    $populate->endPopulate();

                    $childPopulate = $populate->populateSome($child);
                    $populate = $childPopulate;
                }
            }
        }

        $populate->setNestedParent($parent);
        return $populate;
    }

    public function beginAttachRelation(IPopulatableQuery $parent, array $fields, $type = IPopulateQuery::TYPE_ALL, array $selectFields = null)
    {
        $this->_setMode(IQueryTypes::POPULATE);
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flatten($fields);
        $isAll = false;

        switch ($type) {
            case IPopulateQuery::TYPE_ALL:
                $isAll = true;

                // no break
            case IPopulateQuery::TYPE_SOME:
                $this->_joinType = $type;
                break;

            default:
                throw Exceptional::InvalidArgument(
                    $type . ' is not a valid populate type'
                );
        }

        if (count($fields) != 1) {
            throw Exceptional::InvalidArgument(
                'attachRelation() can only handle one field at a time'
            );
        }

        $field = array_shift($fields);

        if (!$populate = $parent->getPopulate($field)) {
            $populate = new Populate($parent, $field, $type, $selectFields);
        }

        $populate->setNestedParent($parent);
        return $populate;
    }



    // Combine
    public function beginCombine(ICombinableQuery $parent, array $fields)
    {
        $this->_setMode(IQueryTypes::COMBINE);
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flatten($fields);

        return new Combine($parent, $fields);
    }



    // Join
    public function beginJoin(IQuery $parent, array $fields = [], $type = IJoinQuery::INNER)
    {
        $this->_setMode(IQueryTypes::JOIN);
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flatten($fields);

        if (empty($fields)) {
            $fields = ['*'];
        }

        switch ($type) {
            case IJoinQuery::INNER:
            case IJoinQuery::LEFT:
            case IJoinQuery::RIGHT:
                $this->_joinType = $type;
                break;

            default:
                throw Exceptional::InvalidArgument(
                    $type . ' is not a valid join type'
                );
        }

        if (isset($fields[0]) && is_array($fields[0])) {
            $fields = $fields[0];
        }

        foreach ($fields as $field) {
            $this->_fieldMap[$field] = null;
        }

        return $this;
    }

    public function beginJoinConstraint(IQuery $parent, $type = IJoinQuery::INNER)
    {
        $this->_setMode(IQueryTypes::JOIN_CONSTRAINT);
        $this->_parentQuery = $parent;

        switch ($type) {
            case IJoinQuery::INNER:
            case IJoinQuery::LEFT:
            case IJoinQuery::RIGHT:
                $this->_joinType = $type;
                break;

            default:
                throw Exceptional::InvalidArgument(
                    $type . ' is not a valid join type'
                );
        }

        return $this;
    }


    // Attach
    public function beginAttach(IReadQuery $parent, array $fields = [], $isSelect = false)
    {
        $this->_parentQuery = $parent;
        $fields = core\collection\Util::flatten($fields, false);

        if (isset($fields[0]) && is_array($fields[0])) {
            $fields = $fields[0];
        }

        if (!$isSelect) {
            $this->_setMode(IQueryTypes::FETCH_ATTACH);
            $this->_fieldMap = ['*' => null];
        } else {
            $this->_setMode(IQueryTypes::SELECT_ATTACH);

            if (empty($fields)) {
                $this->_fieldMap = ['*'];
            } else {
                $this->_fieldMap = $fields;
            }
        }

        return $this;
    }

    public static function beginAttachFromPopulate(IPopulateQuery $populate)
    {
        return $populate->isSelect() ?
            Select_Attach::fromPopulate($populate) :
            Fetch_Attach::fromPopulate($populate);
    }


    // Query data
    public function getFields()
    {
        switch ($this->_mode) {
            case IQueryTypes::SELECT:
            case IQueryTypes::UNION:
            case IQueryTypes::SELECT_ATTACH:
                return $this->_fieldMap;
        }

        return array_keys($this->_fieldMap);
    }

    public function getFieldMap()
    {
        return $this->_fieldMap;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getParentQuery()
    {
        return $this->_parentQuery;
    }

    public function getJoinType()
    {
        return $this->_joinType;
    }

    protected function _setMode($mode)
    {
        if ($this->_mode !== null) {
            throw Exceptional::Logic(
                'Query initiator mode has already been set'
            );
        }

        $this->_mode = $mode;
    }


    public function setDerivationParentInitiator(IInitiator $initiator)
    {
        $this->_derivationParentInitiator = $initiator;
        return $this;
    }

    public function getDerivationParentInitiator()
    {
        return $this->_derivationParentInitiator;
    }


    // Transmutation
    public function from($sourceAdapter, $alias = null)
    {
        foreach ($this->_fieldMap as $key => $field) {
            if ($field instanceof IField) {
                $this->_fieldMap[$key] = $field->getQualifiedName();
            }
        }

        switch ($this->_mode) {
            case IQueryTypes::SELECT:
                $sourceManager = new opal\query\SourceManager($this->_transaction);
                $source = $sourceManager->newSource($sourceAdapter, $alias, $this->getFields());
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support SELECT queries'
                    );
                }

                $output = (new Select($sourceManager, $source))
                    ->isDistinct((bool)$this->_distinct);

                if ($this->_derivationParentInitiator) {
                    $output->setDerivationParentInitiator($this->_derivationParentInitiator);
                }

                return $output;

            case IQueryTypes::UNION:
                $sourceManager = $this->_union->getSourceManager();
                $source = $sourceManager->newSource($sourceAdapter, $alias, $this->getFields(), false, true);
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support UNION queries'
                    );
                }

                $output = (new Select_Union($this->_union, $source))
                    ->isDistinct((bool)$this->_distinct)
                    ->isUnionDistinct((bool)$this->_isUnionDistinct);

                if ($this->_derivationParentInitiator) {
                    $output->setDerivationParentInitiator($this->_derivationParentInitiator);
                }

                return $output;

            case IQueryTypes::FETCH:
                $sourceManager = new opal\query\SourceManager($this->_transaction);
                $source = $sourceManager->newSource($sourceAdapter, $alias, ['*']);
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support FETCH queries'
                    );
                }

                return new Fetch($sourceManager, $source);

            case IQueryTypes::DELETE:
                $sourceManager = new opal\query\SourceManager($this->_transaction);
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support DELETE queries'
                    );
                }

                return new Delete($sourceManager, $source);

            case IQueryTypes::CORRELATION:
                $sourceManager = new opal\query\SourceManager($this->_transaction);
                $sourceManager->setParentSourceManager($this->_parentQuery->getSourceManager());
                $fieldName = $fieldAlias = null;

                foreach ($this->_fieldMap as $fieldName => $fieldAlias) {
                    break;
                }

                if ($fieldAlias !== null) {
                    $fieldName = explode(' as ', $fieldName);
                    $fieldName = array_shift($fieldName) . ' as ' . $fieldAlias;
                }

                $source = $sourceManager->newSource($sourceAdapter, $alias, [$fieldName]);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support CORRELATION queries'
                    );
                }

                $output = new Correlation($this->_parentQuery, $sourceManager, $source, $fieldAlias);

                if ($this->_applicator) {
                    $output->setApplicator($this->_applicator);
                }

                return $output;

            case IQueryTypes::JOIN:
            case IQueryTypes::JOIN_CONSTRAINT:
                $sourceManager = $this->_parentQuery->getSourceManager();

                /*
                if($sourceManager->getSourceByAlias($alias)) {
                    throw Exceptional::Logic(
                        'A source has already been aliased as "'.$alias.'" - join source aliases must be unique'
                    );
                }
                 */


                $fields = null;

                if ($this->_mode === IQueryTypes::JOIN) {
                    $fields = $this->getFields();
                }

                $source = $sourceManager->newSource($sourceAdapter, $alias, $fields);

                if ($source->getAdapterHash() == $this->_parentQuery->getSource()->getAdapterHash()) {
                    if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                        throw Exceptional::Logic(
                            'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() .
                            ' does not support joins'
                        );
                    }
                } else {
                    if (!$source->getAdapter()->supportsQueryType(IQueryTypes::REMOTE_JOIN)) {
                        throw Exceptional::Logic(
                            'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() .
                            ' does not support remote joins'
                        );
                    }
                }

                return new Join(
                    $this->_parentQuery,
                    $source,
                    $this->_joinType,
                    $this->_mode === IQueryTypes::JOIN_CONSTRAINT
                );

            case IQueryTypes::SELECT_ATTACH:
            case IQueryTypes::FETCH_ATTACH:
                if ($alias === null) {
                    throw Exceptional::InvalidArgument(
                        'Attachment sources must be aliased'
                    );
                }

                $fields = $this->getFields();

                $sourceManager = new opal\query\SourceManager($this->_transaction);
                $sourceManager->setParentSourceManager($this->_parentQuery->getSourceManager());

                $source = $sourceManager->newSource($sourceAdapter, $alias, $fields);
                $source->isPrimary(true);

                if ($source->getAdapterHash() == $this->_parentQuery->getSource()->getAdapterHash()) {
                    if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                        throw Exceptional::Logic(
                            'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() .
                            ' does not support attachments'
                        );
                    }
                } else {
                    if (!$source->getAdapter()->supportsQueryType(IQueryTypes::REMOTE_ATTACH)) {
                        throw Exceptional::Logic(
                            'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() .
                            ' does not support remote attachments'
                        );
                    }
                }

                if ($this->_mode == IQueryTypes::FETCH_ATTACH) {
                    return new Fetch_Attach($this->_parentQuery, $sourceManager, $source);
                } else {
                    return (new Select_Attach($this->_parentQuery, $sourceManager, $source))
                        ->isDistinct((bool)$this->_distinct);
                }


                // no break
            case null:
                throw Exceptional::Logic(
                    'Query initiator mode has not been set'
                );

            default:
                throw Exceptional::Logic(
                    'Query initiator mode ' . self::modeIdToName($this->_mode) . ' is not compatible with \'from\' syntax'
                );
        }
    }

    public function fromUnion()
    {
        return self::factory()->beginUnion()
            ->setDerivationParentInitiator($this);
    }

    public function fromSelect(...$fields)
    {
        return self::factory()->beginSelect($fields, false)
            ->setDerivationParentInitiator($this);
    }

    public function fromSelectDistinct(...$fields)
    {
        return self::factory()->beginSelect($fields, true)
            ->setDerivationParentInitiator($this);
    }

    public function into($sourceAdapter, $alias = null)
    {
        $sourceManager = new SourceManager($this->_transaction);

        switch ($this->_mode) {
            case IQueryTypes::INSERT:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support INSERT'
                    );
                }

                return new Insert($sourceManager, $source, $this->_data);

            case IQueryTypes::BATCH_INSERT:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support batch INSERT queries'
                    );
                }

                return new BatchInsert($sourceManager, $source, $this->_data);

            case null:
                throw Exceptional::Logic(
                    'Query initiator mode has not been set'
                );

            default:
                throw Exceptional::Logic(
                    'Query initiator mode ' . self::modeIdToName($this->_mode) . ' is not compatible with \'into\' syntax'
                );
        }
    }

    public function in($sourceAdapter, $alias = null)
    {
        $sourceManager = new opal\query\SourceManager($this->_transaction);

        switch ($this->_mode) {
            case IQueryTypes::REPLACE:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support REPLACE queries'
                    );
                }

                return new Insert($sourceManager, $source, $this->_data, true);

            case IQueryTypes::BATCH_REPLACE:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support batch REPLACE queries'
                    );
                }

                return new BatchInsert($sourceManager, $source, $this->_data, true);

            case IQueryTypes::UPDATE:
                $source = $sourceManager->newSource($sourceAdapter, $alias, null, true);
                $source->isPrimary(true);

                if (!$source->getAdapter()->supportsQueryType($this->_mode)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support UPDATE queries'
                    );
                }

                return new Update($sourceManager, $source, $this->_data);

            case null:
                throw Exceptional::Logic(
                    'Query initiator mode has not been set'
                );

            default:
                throw Exceptional::Logic(
                    'Query initiator mode ' . self::modeIdToName($this->_mode) . ' is not compatible with \'in\' syntax'
                );
        }
    }
}
