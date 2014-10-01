<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link;

use df;
use df\core;
use df\link;
use df\halo;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IIp extends core\IStringProvider {
    // Ranges
    public function isInRange($range);
    public function isV4();
    public function isStandardV4();
    public function isV6();
    public function isStandardV6();
    public function isHybrid();
    public function convertToV6();
    
    // Strings
    public function getV6String();
    public function getCompressedV6String();
    public function getV4String();
    
    // Base conversion
    public function getV6Decimal();
    public function getV4Decimal();
    public function getV6Hex();
    public function getV4Hex();
    
    // Loopback
    public static function getV4Loopback();
    public static function getV6Loopback();
    public function isLoopback();
    public function isV6Loopback();
    public function isV4Loopback();
}


interface IIpRange extends core\IStringProvider {
    public function check($ip);
}




interface IPeer extends halo\event\IDispatcherProvider {
    public function getProtocolDisposition();
}

interface IClient extends IPeer {
    
    const PEER_FIRST = 1;
    const CLIENT_FIRST = 2;
    const PEER_STREAM = 3;
    const CLIENT_STREAM = 4;
    const DUPLEX_STREAM = 5;
    
    public function run();
}

interface IServer extends IPeer {
    
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
    const WRITE = 1;
    const WRITE_LISTEN = 2;
    const OPEN_WRITE = 3;
    const READ = 5;
    const READ_LISTEN = 6;
    const OPEN_READ = 7;
    const END = 9;
}