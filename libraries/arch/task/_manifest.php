<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\task;

use df;
use df\core;
use df\arch;

// Exceptions
interface IException {}


// Interfaces
interface IAction extends arch\IAction {
    public function runChild($request);
}


interface IManager extends core\IManager {
    public function launch($request, core\io\IMultiplexer $multiplexer=null, $environmentMode=null);
    public function launchBackground($request, $environmentMode=null);
    public function invoke($request);
    public function initiateStream($request, $environmentMode=null);
    public function getResponse();
}
