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
    public function getPidFilePath();

    public function run();
    public function isRunning();
    public function stop();
    public function isStopped();
    public function restart();

    public function pause();
    public function isPaused();
    public function resume();
}


interface IRemote {
    public function getName();
    public function isRunning();
    public function getStatusData();
    public function getProcess();
    public function refresh();
    public function start();
    public function stop();
    public function restart();
    public function pause();
    public function resume();
    public function nudge();
}


interface IManager extends core\IManager {
    public function isEnabled();
    public function ensureActivity();

    public function launch($name);
    public function nudge($name);
    public function getRemote($name);
    public function isRunning($name);
}