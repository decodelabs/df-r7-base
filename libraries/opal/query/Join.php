<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Join implements IJoinQuery, core\IDumpable {
    
    use TQuery_ParentAware;
    use TQuery_JoinClauseFactoryBase;
    
    protected $_source;
    protected $_type;
    
    public static function typeIdToName($id) {
        switch($id) {
            case IJoinQuery::INNER:
                return 'INNER';
                
            case IJoinQuery::LEFT:
                return 'LEFT';
                
            case IJoinQuery::RIGHT:
                return 'RIGHT';
        }
    }
    
    public function __construct(IJoinableQuery $parent, ISource $source, $type=self::INNER) {
        $this->_parent = $parent;
        $this->_source = $source;
        
        switch($type) {
            case IJoinQuery::INNER:
            case IJoinQuery::LEFT:
            case IJoinQuery::RIGHT:
                $this->_type = $type;
                break;
                
            default:
                throw new InvalidArgumentException(
                    $type.' is not a valid join type'
                );
        }
    }
    
    public function getQueryType() {
        return IQueryTypes::JOIN;
    }
    
    
// Type
    public function getType() {
        return $this->_type;
    }
    
    
// Sources
    public function getSourceManager() {
        return $this->_parent->getSourceManager();
    }
    
    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }
    
    public function addOutputFields($fields) {
        if(!is_array($fields)) {
            $fields = func_get_args();
        }
        
        $sourceManager = $this->getSourceManager();
        
        foreach($fields as $field) {
            $sourceManager->extrapolateOutputField($this->_source, $field);
        }
        
        return $this;
    }
    
    
    
// Join clauses
    public function on($localField, $operator, $foreignField) {
        $manager = $this->getSourceManager();
        
        $this->getJoinClauseList()->addJoinClause(
            opal\query\clause\Clause::factory(
                $this,
                $manager->extrapolateIntrinsicField($this->_source, $localField, true),
                $operator,
                $manager->extrapolateIntrinsicField(
                    $this->_parent->getSource(), 
                    $foreignField, 
                    $this->_source->getAlias()
                ),
                false
            )
        );
        
        return $this;
    }

    public function orOn($localField, $operator, $foreignField) {
        $manager = $this->getSourceManager();
        
        $this->getJoinClauseList()->addJoinClause(
            opal\query\clause\Clause::factory(
                $this,
                $manager->extrapolateIntrinsicField($this->_source, $localField, true),
                $operator,
                $manager->extrapolateIntrinsicField(
                    $this->_parent->getSource(), 
                    $foreignField, 
                    $this->_source->getAlias()
                ),
                true
            )
        );
        
        return $this;
    }
    
    public function endJoin() {
        $this->_parent->addJoin($this);
        return $this->_parent;
    }
    
    
// Dump
    public function getDumpProperties() {
        return array(
            'type' => self::typeIdToName($this->_type),
            'fields' => $this->_source,
            'on' => $this->_joinClauseList
        );
    }
}
