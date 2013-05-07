<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class JoinConstraint extends Join implements IJoinConstraintQuery {
    
    public function __construct(IJoinConstrainableQuery $parent, ISource $source, $type=self::INNER) {
        $this->_parent = $parent;
        $this->_source = $source;
        
        switch($type) {
            case self::INNER:
            case self::LEFT:
            case self::RIGHT:
                $this->_type = $type;
                break;
                
            default:
                throw new InvalidArgumentException(
                    $type.' is not a valid join type'
                );
        }
        
        $this->_joinClauseList = new opal\query\clause\JoinList($this);        
    }
    
    public function getQueryType() {
        return IQueryTypes::JOIN_CONSTRAINT;
    }
    
    public function endJoin() {
        $this->_parent->addJoinConstraint($this);
        return $this->getNestedParent();
    }
    
    
// Dump
    public function getDumpProperties() {
        return array(
            'type' => self::typeIdToName($this->_type),
            'source' => $this->_source->getAdapter()->getQuerySourceDisplayName(),
            'on' => $this->_joinClauseList
        );
    }
}
