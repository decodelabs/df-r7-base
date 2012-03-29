<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\clause;

use df;
use df\core;
use df\opal;

class WhereList extends ListBase implements opal\query\IWhereClauseList {
    
    protected $_isPrerequisite = false;
    
    public function __construct(opal\query\IClauseFactory $parent, $isOr=false, $isPrerequisite=false) {
        if($isPrerequisite) {
            $isOr = false;
            
            if(!$parent instanceof opal\query\IPrerequisiteClauseFactory) {
                throw new opal\query\InvalidArgumentException(
                    'Parent query is not capable of handling prerequisites'
                );
            }
        }
        
        
        parent::__construct($parent, $isOr);
        $this->_isPrerequisite = $isPrerequisite;
    }
    
    public function where($field, $operator, $value) {
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
    
    public function orWhere($field, $operator, $value) {
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

    public function beginWhereClause() {
        return new WhereList($this);
    }
    
    public function beginOrWhereClause() {
        return new WhereList($this, true);
    }
    
    
    public function addWhereClause(opal\query\IWhereClauseProvider $clause=null) {
        $this->_addClause($clause);
        return $this;
    }
    
    public function getWhereClauseList() {
        return $this;
    }
    
    public function hasWhereClauses() {
        return !$this->isEmpty();
    }
    
    public function clearWhereClauses() {
        return $this->clear();
    }
    
    public function endClause() {
        if(!empty($this->_clauses)) {
            if($this->_isPrerequisite) {
                $this->_parent->addPrerequisite($this);
            } else {
                $this->_parent->addWhereClause($this);
            }
        }
        
        return $this->_parent;
    }
}
