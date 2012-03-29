<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\clause;

use df;
use df\core;
use df\opal;

class ListBase implements opal\query\IClauseList, core\IDumpable {
    
    protected $_isOr = false;
    protected $_clauses = array();
    protected $_parent;
    
    public function __construct(opal\query\IClauseFactory $parent, $isOr=false) {
        $this->_parent = $parent;
        $this->_isOr = (bool)$isOr;
    }
    
    public function toArray() {
        return $this->_clauses;
    }
    
    public function isOr($flag=null) {
        if($flag !== null) {
            $this->_isOr = (bool)$flag;
            return $this;
        }
        
        return $this->_isOr;
    }
    
    public function isAnd($flag=null) {
        if($flag !== null) {
            $this->_isOr = !(bool)$flag;
            return $this;
        }
        
        return !$this->_isOr;
    }
    
    public function _addClause(opal\query\IClauseProvider $clause=null) {
        if($clause !== null) {
            $this->_clauses[] = $clause;
        }
        
        return $this;
    }
    
    public function isEmpty() {
        return empty($this->_clauses);
    }
    
    public function count() {
        return count($this->_clauses);
    }
    
    public function getSourceManager() {
        return $this->_parent->getSourceManager();
    }
    
    public function getSource() {
        return $this->_parent->getSource();
    }
    
    public function getSourceAlias() {
        return $this->_parent->getSourceAlias();
    }
    
    public function referencesSourceAliases(array $sourceAliases) {
        foreach($this->_clauses as $clause) {
            if($clause->referencesSourceAliases($sourceAliases)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getNonLocalFieldReferences() {
        $output = array();
        
        foreach($this->_clauses as $clause) {
            $output = array_merge($output, $clause->getNonLocalFieldReferences());
        }
        
        return $output;
    }
    
    public function getClausesFor(opal\query\ISource $source, opal\query\IClauseFactory $parent=null) {
        if($parent === null) {
            $parent = $this->_parent;
        }
        
        $class = get_class($this);
        $output = new $class($parent);
        
        foreach($this->_clauses as $clause) {
            if($clause instanceof opal\query\IClauseList) {
                if(null !== ($newClause = $clause->getClausesFor($source, $output))) {
                    $output->_clauses[] = $newClause;
                }
            } else {
                if($source->getAlias() == $clause->getField()->getSource()->getAlias()) {
                    $output->_clauses[] = $clause;
                } else if($clause->isOr()) {
                    return null;
                }
            }
        }
        
        if(empty($output->_clauses)) {
            return null;
        }
        
        return $output;
    }
    
    public function clear() {
        $this->_clauses = array();
        return $this;
    }
    
    public function endClause() {
        return $this->_parent;
    }
    
// Dump
    public function getDumpProperties() {
        $output = array();
        
        if($this->_parent instanceof opal\query\IClauseList) {
            $output['**type'] = $this->_isOr ? 'OR' : 'AND';
        }
        
        return array_merge($output, $this->_clauses);
    }
}
