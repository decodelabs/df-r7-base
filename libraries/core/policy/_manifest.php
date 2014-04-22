<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\policy;

use df;
use df\core;
use df\mesh;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
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
    public function fetchEntity(IManager $manager, mesh\entity\ILocatorNode $node);
}

interface IEventHandler extends IHandler, IEventReceiver {}



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

interface IHook extends core\IContextAware {
    public static function triggerEvent(IEvent $event);
    public function getName();
    public function getActionMap();
}

class HookCache extends core\cache\Base {}