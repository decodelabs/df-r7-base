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
    public function fetchEntity(IManager $manager, array $node);
}

interface IEventHandler extends IHandler, mesh\event\IEmitter {}



// Callback
interface ICallback {
    const DIRECT = 1;
    const REFLECTION = 2;

    public function setExtraArgs(array $args);
    public function getExtraArgs();

    public function invoke();
    public function invokeArgs(array $args);
}