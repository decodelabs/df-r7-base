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
    
    use TQuery;
    use TQuery_ParentAware;
    use TQuery_ParentAwareJoinClauseFactory;
    use TQuery_NestedComponent;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_WhereClauseFactory;
    
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
    
    public function endJoin() {
        $this->_parent->addJoin($this);
        return $this->getNestedParent();
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = [
            'type' => self::typeIdToName($this->_type),
            'fields' => $this->_source,
            'on' => $this->_joinClauseList
        ];

        if($this->hasWhereClauses()) {
            $output['where'] = $this->getWhereClauseList();
        }

        return $output;
    }
}
