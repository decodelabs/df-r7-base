<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\peer;

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

interface IServer extends halo\event\IAdaptiveListener {
    public function getProtocolDisposition();
    public function setDispatcher(halo\event\IDispatcher $dispatcher);
    public function getDispatcher();
    public function start();
    public function stop();
}


interface ISession {
    public function getId();
    public function getSocket();
}


interface IIoState {
    const BUFFER = null;
    const WRITE = 1;
    const OPEN_WRITE = 2;
    const READ = 3;
    const OPEN_READ = 4;
    const END = 5;
}
