<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\policy;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class EntityNotFoundException extends RuntimeException {}


// Interfaces
interface IManager extends core\IManager {
    public function registerHandler($scheme, IHandler $handler);
    public function unregisterHandler($scheme);
    public function getHandler($scheme);
    public function getHandlers();
    public function fetchEntity($url);
}



interface IHandler {}

interface IEntityHandler extends IHandler {
    public function fetchEntity(IManager $manager, IEntityLocatorNode $node);
}


interface IEntity {}

interface IParentEntity extends IEntity {
    public function fetchSubEntity(IManager $manager, IEntityLocatorNode $node);
}


interface IEntityLocator extends core\IStringProvider  {
    public function getScheme();
    public function getNodes();
    public function getFirstNode();
    public function getFirstNodeType();
    public function getFirstNodeId();
    public function toStringUpTo($type);
}


interface IEntityLocatorNode extends core\IStringProvider {
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