<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map;

use df;
use df\core;
use df\iris;
    
class Unit extends Node implements IUnit, core\IDumpable {

    protected $_statements = [];
    protected $_entities = [];

// Statements
    public function addStatement(IStatement $statement) {
        $this->_statements[$statement->getLocationId()] = $statement;
        return $this;
    }

    public function getStatements() {
        return $this->_statements;
    }

// Entities
    public function addEntity(IEntity $entity) {
        $this->_entities[$entity->getLocationId()] = $entity;
        return $this;
    }

    public function getEntities() {
        return $this->_entities;
    }


// Dump
    public function getDumpProperties() {
        return [
            'statements' => $this->_statements,
            'entities' => $this->_entities
        ];
    }
}