<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\event;

use df;
use df\core;
use df\mesh;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IEmitter {
    public function emitEvent($entity, $action, array $data=null);
    public function emitEventObject(IEvent $event);
}

trait TEmitter {

    public function emitEvent($entity, $action, array $data=null) {
        return $this->emitEventObject(new mesh\event\Event($entity, $action, $data));
    }
}

interface IDispatcher extends IEmitter {
    public function bind($entity, $action, Callable $listener);
    public function addBinding(IBinding $binding);
}

interface IGenerator {

}

interface IBinding {
    public function getId();
    public function setEntityLocator($entityLocator);
    public function getEntityLocator();
    public function setAction($action);
    public function getAction();
    public function setListener(Callable $listener);
    public function getListener();
}


interface IEvent extends core\collection\IMap {
    // Entity
    public function setEntity(mesh\entity\ILocatorProvider $entity);
    public function hasEntity();
    public function getEntity();
    public function hasCachedEntity();
    public function getCachedEntity();
    public function clearCachedEntity();
    public function getEntityLocator();

    // Action
    public function setAction($action);
    public function getAction();
}


interface IHook extends core\IContextAware {
    public static function triggerEvent(IEvent $event);
    public function getName();
    public function getActionMap();
}

class HookCache extends core\cache\Base {}