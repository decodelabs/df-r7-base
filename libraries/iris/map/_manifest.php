<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map;

use df;
use df\core;
use df\iris;

// Exceptions
interface IException {}

class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}

// Interfaces
interface INode extends iris\ILocationProvider {
    public function getLocationId();
    public function replaceLocation(iris\ILocationProvider $locationProvider);
    public function duplicate(iris\ILocationProvider $locationProvider=null);
    public function normalize();
}


interface IUnit extends INode {
    // Statements
    public function addStatement(IStatement $statement);
    public function getStatements();

    // Entities
    public function addEntity(IEntity $entity);
    public function getEntities();
}


interface IStatement extends INode {

}

interface IEntity extends INode {

}

interface IAspect extends INode {
    
}