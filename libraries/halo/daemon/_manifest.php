<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\daemon;

use df;
use df\core;
use df\halo;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class LogicException extends \LogicException implements IException {}


// Interfaces
interface IDaemon extends halo\event\IDispatcherProvider {
    public function getName();

    public function start();
    public function cycle();
    public function isStarted();
    public function stop();
    public function isStopped();
    public function pause();
    public function resume();
    public function isPaused();
}
    
interface IAngel extends IDaemon {

}