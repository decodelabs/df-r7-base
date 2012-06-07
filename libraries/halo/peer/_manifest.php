<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\client;

use df;
use df\core;
use df\halo;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IClient extends halo\event\IAdaptiveListener {
    public function getProtocolDisposition();
    public function setDispatcher(halo\event\IDispatcher $dispatcher);
    public function getDispatcher();
    public function run();
}