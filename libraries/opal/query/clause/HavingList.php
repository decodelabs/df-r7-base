<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\clause;

use df;
use df\core;
use df\opal;

class HavingList extends ListBase implements opal\query\IHavingClauseList {
    
    public function having($field, $operator, $value) {
        $this->addHavingClause(
            Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateAggregateField($this->getSource(), $field), 
                $operator, 
                $value, 
                false
            )
        );
        
        return $this;
    }
    
    public function orHaving($field, $operator, $value) {
        $this->addHavingClause(
            Clause::factory(
                $this,
                $this->getSourceManager()->extrapolateAggregateField($this->getSource(), $field), 
                $operator, 
                $value, 
                true
            )
        );
        
        return $this;
    }
    
    public function beginHavingClause() {
        return new HavingList($this);
    }
    
    public function beginOrHavingClause() {
        return new HavingList($this, true);
    }
    
    
    public function addHavingClause(opal\query\IHavingClauseProvider $clause=null) {
        return $this->_addClause($clause);
    }
    
    public function getHavingClauseList() {
        return $this;
    }
    
    public function hasHavingClauses() {
        return !$this->isEmpty();
    }
    
    public function clearHavingClauses() {
        return $this->clear();
    }
    
    public function endClause() {
        if(!empty($this->_clauses)) {
            $this->_parent->addHavingClause($this);
        }
        
        return $this->_parent;
    }
}
