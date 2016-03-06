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
    protected $_isConstraint = false;

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

    public function __construct(IQuery $parent, ISource $source, $type=self::INNER, $isConstraint=false) {
        $this->_parent = $parent;
        $this->_source = $source;
        $this->_isConstraint = $isConstraint;

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

        //$this->_joinClauseList = new opal\query\clause\JoinList($this);
    }

    public function getQueryType() {
        if($this->_isConstraint) {
            return IQueryTypes::JOIN_CONSTRAINT;
        } else {
            return IQueryTypes::JOIN;
        }
    }


// Type
    public function getType() {
        return $this->_type;
    }

    public function isConstraint($flag=null) {
        if($flag !== null) {
            $this->_isConstraint = (bool)$flag;
            return $this;
        }

        return $this->_isConstraint;
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

    public function addOutputFields(string ...$fields) {
        $sourceManager = $this->getSourceManager();

        foreach($fields as $field) {
            $sourceManager->extrapolateOutputField($this->_source, $field);
        }

        return $this;
    }

    public function endJoin() {
        if($this->_isConstraint) {
            $this->_parent->addJoinConstraint($this);
        } else {
            $this->_parent->addJoin($this);
        }

        return $this->getNestedParent();
    }


// Dump
    public function getDumpProperties() {
        $output = [
            'type' => self::typeIdToName($this->_type).($this->_isConstraint ? ' constraint' : null),
            'fields' => $this->_source,
            'on' => $this->_joinClauseList
        ];

        if($this->hasWhereClauses()) {
            $output['where'] = $this->getWhereClauseList();
        }

        return $output;
    }
}
