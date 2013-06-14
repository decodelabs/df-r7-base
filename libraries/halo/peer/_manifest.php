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
interface IPeer extends halo\event\IDispatcherProvider {
    public function getProtocolDisposition();
}

interface IClient extends halo\event\IAdaptiveListener, IPeer {
    
    const PEER_FIRST = 1;
    const CLIENT_FIRST = 2;
    const PEER_STREAM = 3;
    const CLIENT_STREAM = 4;
    const DUPLEX_STREAM = 5;
    
    public function run();
}

interface IServer extends halo\event\IAdaptiveListener, IPeer {
    
    const SERVER_FIRST = 1;
    const PEER_FIRST = 2;
    const SERVER_STREAM = 3;
    const PEER_STREAM = 4;
    const DUPLEX_STREAM = 5;
    
    public function start();
    public function stop();
}


interface ISession {
    public function getId();
    public function getSocket();
    public function setWriteState($state);
    public function getWriteState();

    public function setStore($key, $value);
    public function hasStore($key);
    public function getStore($key, $default=null);
    public function removeStore($key);
    public function clearStore();
}

interface IRequestResponseSession {
    public function setRequest(ISessionRequest $request);
    public function getRequest();
    
    public function setResponse(ISessionResponse $response);
    public function getResponse();
}

interface ISessionRequest {}
interface ISessionResponse {}


interface IIoState {
    const BUFFER = null;
    const WRITE = 1;
    const OPEN_WRITE = 2;
    const READ = 3;
    const OPEN_READ = 4;
    const END = 5;
}
