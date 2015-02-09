<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\routine;

use df;
use df\core;
use df\axis;
use df\opal;

// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IRoutine extends core\IContextAware {
    public function getUnit();
    public function getModel();
    public function getName();
    public function setMultiplexer(core\io\IMultiplexer $multiplexer);
    public function getMultiplexer();
    public function execute();
}

interface IConsistencyRoutine extends IRoutine {
    public function canExecute();
}