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
interface IException
{
}
class RuntimeException extends \RuntimeException implements IException
{
}
class InvalidArgumentException extends \InvalidArgumentException implements IException
{
}
class UnexpectedValueException extends \UnexpectedValueException implements IException
{
}


// Interfaces
interface ILocatorProvider
{
    public function getEntityLocator();
}

interface IEntity extends ILocatorProvider
{
}

interface IParentEntity extends IEntity
{
    public function fetchSubEntity(mesh\IManager $manager, array $node);
}

interface IActiveParentEntity extends IParentEntity
{
    public function getSubEntityLocator(IEntity $entity);
}

interface ILocator extends ILocatorProvider, core\IStringProvider
{
    public function setScheme($scheme);
    public function getScheme();

    public function setNodes(array $nodes);
    public function addNodes(array $nodes);
    public function addNode($location, $type, $id=null);
    public function importNode(array $node);
    public function setNode($index, $location, $type, $id=null);
    public function setNodeArray($index, array $node);
    public function hasNode($index);
    public function getNode($index);
    public function getNodeString($index);
    public function setNodeLocation($index, $location);
    public function appendNodeLocation($index, $location);
    public function getNodeLocation($index);
    public function setNodeType($index, $type);
    public function getNodeType($index);
    public function setNodeId($index, $id);
    public function getNodeId($index);
    public function removeNode($index);
    public function getNodes();

    public function setFirstNode($location, $type, $id=null);
    public function getFirstNode();
    public function setFirstNodeLocation($location);
    public function getFirstNodeLocation();
    public function setFirstNodeType($type);
    public function getFirstNodeType();
    public function setFirstNodeId($id);
    public function getFirstNodeId();

    public function setLastNode($location, $type, $id=null);
    public function getLastNode();
    public function setLastNodeLocation($location);
    public function getLastNodeLocation();
    public function setLastNodeType($type);
    public function getLastNodeType();
    public function setLastNodeId($id);
    public function getLastNodeId();

    public function getDomain();
    public function setId(?string $id);
    public function getId(): ?string;

    public function toStringUpTo($type);
}
