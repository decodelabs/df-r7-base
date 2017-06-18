<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\clause;

use df;
use df\core;
use df\opal;

class JoinList extends ListBase implements opal\query\IJoinClauseList {


    public function on($localField, $operator, $foreignField) {
        $source = $this->getSource();

        $this->addJoinClause(
            Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($source, $localField, true),
                $operator,
                $this->getParentSourceManager()->extrapolateIntrinsicField(
                    $this->getParentSource(),
                    $foreignField,
                    $source->getAlias()
                ),
                false
            )
        );

        return $this;
    }

    public function orOn($localField, $operator, $foreignField) {
        $manager = $this->getSourceManager();
        $source = $this->getSource();

        $this->addJoinClause(
            Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateIntrinsicField($source, $localField, true),
                $operator,
                $this->getParentSourceManager()->extrapolateIntrinsicField(
                    $this->getParentSource(),
                    $foreignField,
                    $source->getAlias()
                ),
                true
            )
        );

        return $this;
    }

    public function beginOnClause() {
        return new JoinList($this);
    }

    public function beginOrOnClause() {
        return new JoinList($this, true);
    }


    public function addJoinClause(opal\query\IJoinClauseProvider $clause=null) {
        return $this->_addClause($clause);
    }

    public function getJoinClauseList() {
        return $this;
    }

    public function hasJoinClauses() {
        return !$this->isEmpty();
    }

    public function clearJoinClauses() {
        return $this->clear();
    }

    public function getParentSourceManager() {
        if($this->_parent instanceof opal\query\IAttachQuery
        || !$this->_parent instanceof opal\query\IParentSourceProvider) {
            return $this->_parent->getSourceManager();
        } else {
            return $this->_parent->getParentSourceManager();
        }
    }

    public function getParentSource() {
        if($this->_parent instanceof opal\query\IJoinProviderQuery
        || $this->_parent instanceof opal\query\IAttachProviderQuery
        || !$this->_parent instanceof opal\query\IParentSourceProvider) {
            return $this->_parent->getSource();
        } else {
            return $this->_parent->getParentSource();
        }
    }

    public function getParentSourceAlias() {
        return $this->getParentSource()->getAlias();
    }

    public function endClause() {
        if(!empty($this->_clauses)) {
            $this->_parent->addJoinClause($this);
        }

        return $this->_parent;
    }


    public function getLocalFieldList() {
        $output = [];

        foreach($this->_clauses as $clause) {
            if($clause instanceof opal\query\IClauseList) {
                $output = array_merge($output, $clause->getLocalFieldList());
            } else {
                $field = $clause->getField();
                $output[$field->getQualifiedName()] = $field;
            }
        }

        return $output;
    }
}
