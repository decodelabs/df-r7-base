<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\entity;

use df;
use df\core;
use df\mesh;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class EntityNotFoundException extends RuntimeException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface ILocatorProvider {
    public function getEntityLocator();
}

interface IEntity extends ILocatorProvider {}

interface IParentEntity extends IEntity {
    public function fetchSubEntity(mesh\IManager $manager, ILocatorNode $node);
}

interface IActiveParentEntity extends IParentEntity {
    public function getSubEntityLocator(IEntity $entity);
}

interface ILocator extends ILocatorProvider, core\IStringProvider  {
    public function setScheme($scheme);
    public function getScheme();

    public function setNodes(array $nodes);
    public function addNodes(array $nodes);
    public function addNode(ILocatorNode $node);
    public function getNode($index);
    public function getNodeType($index);
    public function getNodeId($index);
    public function hasNode($index);
    public function removeNode($index);
    public function getNodes();

    public function getFirstNode();
    public function getFirstNodeType();
    public function getFirstNodeId();

    public function getLastNode();
    public function getLastNodeType();
    public function getLastNodeId();

    public function getDomain();
    public function setId($id);
    public function getId();

    public function toStringUpTo($type);
}


interface ILocatorNode extends core\IStringProvider {
    public function setLocation($location);
    public function appendLocation($location);
    public function getLocation();
    public function getLocationArray();
    public function hasLocation();
    public function setType($type);
    public function getType();
    public function setId($id);
    public function getId();
}