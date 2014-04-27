<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh;

use df;
use df\core;
use df\mesh;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IManager extends mesh\event\IEmitter, core\IManager {
    // Handlers
    public function registerHandler($scheme, IHandler $handler);
    public function unregisterHandler($scheme);
    public function getHandler($scheme);
    public function getHandlers();

    // Entities
    public function fetchEntity($locator);
}



// Entity
interface IHandler {}

interface IEntityHandler extends IHandler {
    public function fetchEntity(IManager $manager, mesh\entity\ILocatorNode $node);
}

interface IEventHandler extends IHandler, mesh\event\IEmitter {}
