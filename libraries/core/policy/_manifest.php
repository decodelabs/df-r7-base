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
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IManager extends IEventReceiver, core\IManager {
    // Handlers
    public function registerHandler($scheme, IHandler $handler);
    public function unregisterHandler($scheme);
    public function getHandler($scheme);
    public function getHandlers();

    // Entities
    public function fetchEntity($locator);

    // Events
    public function triggerEntityEvent($locator, $action, array $data=null);
    public function triggerHandlerEvent($handler, $action, array $data=null);
}



// Entity
interface IHandler {}

interface IEntityHandler extends IHandler {
    public function fetchEntity(IManager $manager, IEntityLocatorNode $node);
}

interface IEventHandler extends IHandler, IEventReceiver {}


interface IEntityLocatorProvider {
    public function getEntityLocator();
}

interface IEntity extends IEntityLocatorProvider {}

interface IParentEntity extends IEntity {
    public function fetchSubEntity(IManager $manager, IEntityLocatorNode $node);
}

interface IActiveParentEntity extends IParentEntity {
    public function getSubEntityLocator(IEntity $entity);
}


interface IEntityLocator extends IEntityLocatorProvider, core\IStringProvider  {
    public function setScheme($scheme);
    public function getScheme();

    public function setNodes(array $nodes);
    public function addNodes(array $nodes);
    public function addNode(IEntityLocatorNode $node);
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




// Event
interface IEvent extends core\collection\IMap {
    // Entity
    public function setEntity($locator);
    public function hasEntityLocator();
    public function getEntityLocator();
    public function hasCachedEntity();
    public function getCachedEntity();
    public function clearCachedEntity();

    // Handler
    public function setHandler($handler);
    public function hasHandler();
    public function getHandler();

    // Action
    public function setAction($action);
    public function getAction();
}

interface IEventReceiver {
    public function triggerEvent(IEvent $event);
}

interface IHook {
    public static function triggerEvent(IEvent $event);
    public function getName();
    public function getActionMap();
}

class HookCache extends core\cache\Base {}