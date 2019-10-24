<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\clause;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch;

class WhereList extends ListBase implements opal\query\IWhereClauseList
{
    protected $_isPrerequisite = false;

    public function __construct(opal\query\IClauseFactory $parent, $isOr=false, $isPrerequisite=false)
    {
        if ($isPrerequisite) {
            $isOr = false;

            if (!$parent instanceof opal\query\IPrerequisiteClauseFactory) {
                throw Glitch::EInvalidArgument(
                    'Parent query is not capable of handling prerequisites'
                );
            }
        }


        parent::__construct($parent, $isOr);
        $this->_isPrerequisite = $isPrerequisite;
    }

    public function where($field, $operator, $value)
    {
        $this->addWhereClause(
            Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($this->getSource(), $field),
                $operator,
                $value,
                false
            )
        );

        return $this;
    }

    public function orWhere($field, $operator, $value)
    {
        $this->addWhereClause(
            Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($this->getSource(), $field),
                $operator,
                $value,
                true
            )
        );

        return $this;
    }

    public function whereField($leftField, $operator, $rightField)
    {
        $this->addWhereClause(
            Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($this->getSource(), $leftField),
                $operator,
                $this->getSourceManager()->extrapolateIntrinsicField($this->getSource(), $rightField),
                false
            )
        );

        return $this;
    }

    public function orWhereField($leftField, $operator, $rightField)
    {
        $this->addWhereClause(
            Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($this->getSource(), $leftField),
                $operator,
                $this->getSourceManager()->extrapolateIntrinsicField($this->getSource(), $rightField),
                true
            )
        );

        return $this;
    }



    public function whereCorrelation($field, $operator, $keyField)
    {
        $sourceManager = $this->getSourceManager();

        $initiator = opal\query\Initiator::factory()
            ->setTransaction($sourceManager->getTransaction())
            ->beginCorrelation($this, $keyField);

        $initiator->setApplicator(function ($correlation) use ($field, $operator) {
            $this->where($field, $operator, $correlation);
        });

        return $initiator;
    }

    public function orWhereCorrelation($field, $operator, $keyField)
    {
        $sourceManager = $this->getSourceManager();

        $initiator = opal\query\Initiator::factory()
            ->setTransaction($sourceManager->getTransaction())
            ->beginCorrelation($this, $keyField);

        $initiator->setApplicator(function ($correlation) use ($field, $operator) {
            $this->orWhere($field, $operator, $correlation);
        });

        return $initiator;
    }

    public function beginWhereClause()
    {
        return new WhereList($this);
    }

    public function beginOrWhereClause()
    {
        return new WhereList($this, true);
    }


    public function addWhereClause(opal\query\IWhereClauseProvider $clause=null)
    {
        $this->_addClause($clause);
        return $this;
    }

    public function getWhereClauseList()
    {
        return $this;
    }

    public function hasWhereClauses()
    {
        return !$this->isEmpty();
    }

    public function clearWhereClauses()
    {
        return $this->clear();
    }

    public function endClause()
    {
        if (!empty($this->_clauses)) {
            if ($this->_isPrerequisite) {
                $this->_parent->addPrerequisite($this);
            } else {
                $this->_parent->addWhereClause($this);
            }
        }

        return $this->_parent;
    }
}
