<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map;

use df;
use df\core;
use df\iris;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Unit extends Node implements IUnit, Inspectable
{
    protected $_statements = [];
    protected $_entities = [];

    // Statements
    public function addStatement(IStatement $statement)
    {
        $this->_statements[$statement->getLocationId()] = $statement;
        return $this;
    }

    public function getStatements()
    {
        return $this->_statements;
    }

    // Entities
    public function addEntity(IEntity $entity)
    {
        $this->_entities[$entity->getLocationId()] = $entity;
        return $this;
    }

    public function getEntities()
    {
        return $this->_entities;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*statements' => $inspector($this->_statements),
                '*entities' => $inspector($this->_entities)
            ]);
    }
}
