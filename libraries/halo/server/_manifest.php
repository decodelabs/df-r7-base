<?php

namespace df\halo\server;

use df\core;
use df\halo;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}

// Interfaces
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