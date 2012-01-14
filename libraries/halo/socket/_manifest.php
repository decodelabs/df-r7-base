<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket;

use df;
use df\core;
use df\halo;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class ConnectionException extends RuntimeException {}
class IOException extends RuntimeException {}




// Interfaces
interface ISocket {
    public function getId();
    public function getImplementationName();
    public function getAddress();
    public function getSocketDescriptor();
    public function setSessionId($id);
    public function getSessionId();
    
    // Options
    public function getOptions();
    
    public function setSendBufferSize($buffer);
    public function getSendBufferSize();
    public function setReceiveBufferSize($buffer);
    public function getReceiveBufferSize();
    
    public function setSendLowWaterMark($bytes);
    public function getSendLowWaterMark();
    public function setReceiveLowWaterMark($bytes);
    public function getReceiveLowWaterMark();
    
    public function setSendTimeout($timeout);
    public function getSendTimeout();
    public function setReceiveTimeout($timeout);
    public function getReceiveTimeout();
    
    // State
    public function isActive();
    public function isReadingEnabled();
    public function isWritingEnabled();
    
    // Shutdown
    public function shutdownReading();
    public function shutdownWriting();
    public function close();
}

interface IConnectionOrientedSocket extends ISocket {
    public function checkConnection();
}

interface IIoSocket extends ISocket {
    public function peek($length);
    public function read($length);
    public function readAll();
    public function write($data);
    public function writeAll($data);
}

interface ISecureSocket extends ISocket {
    public function isSecure($flag=null);
    public function canSecure();
    public function getSecureTransport();
}

interface ISecureConnectingSocket extends ISecureSocket {
    public function setSecureTransport($transport);
    public function getSecureOptions();
    public function allowSelfSigned($flag=null);
    public function setCommonName($name);
    public function getCommonName();
    public function setCiphers($ciphers);
    public function getCiphers();
}

interface ISequenceSocket extends ISocket, IConnectionOrientedSocket {
    public function shouldSendOutOfBandDataInline($flag=null);
    public function shouldUseSequencePackets($flag=null);
}

interface IDatagramSocket extends ISocket {
    
}

interface IRawSocket extends ISocket {
    
}



// Server
interface IServerSocket extends ISocket {
    // Options
    //public function setConnectionTimeout($timeout);
    //public function getConnectionTimeout();
    public function shouldReuseAddress($flag=null); // should be on by default
    
    // Operation
    public function listen();
    public function isListening();
    public function accept();
}

interface ISecureServerSocket extends IServerSocket, ISecureConnectingSocket {
    public function setLocalCertificate($file);
    public function getLocalCertificate();
    
    public function setPassphrase($passphrase);
    public function getPassphrase();
    
    public function shouldUseSNI($flag=null);
    public function setSNIServerName($name);
    public function getSNIServerName();
}

interface IBroadcastableServerSocket extends IServerSocket  {
    // Options
    public function isBroadcast($flag=null);
}

interface ISequenceServerSocket extends IServerSocket, ISequenceSocket {
    // Options
    public function setConnectionQueueSize($size);
    public function getConnectionQueueSize();
    
    public function shouldLingerOnClose($flag=null, $timeout=null);
    public function setLingerTimeout($timeout);
    public function getLingerTimeout(); 
}

interface IDatagramServerSocket extends IServerSocket, IDatagramSocket, IBroadcastableServerSocket {
    
}

interface IRawServerSocket extends IServerSocket, IRawSocket {
    
}



// Server peer
interface IServerPeerSocket extends ISocket, IIoSocket {
    
}

interface ISecureServerPeerSocket extends IServerPeerSocket, ISecureSocket {
    
}

interface ISequenceServerPeerSocket extends IServerPeerSocket, ISequenceSocket {
    
}

interface IDatagramServerPeerSocket extends IServerPeerSocket, IDatagramSocket {
    
}

interface IRawServerPeerSocket extends IServerPeerSocket, IRawSocket {
    
}




// Client
interface IClientSocket extends ISocket, IIoSocket {
    // Options
    public function setConnectionTimeout($timeout);
    public function getConnectionTimeout();
    
    // Operation
    public function connect();
}

interface ISecureClientSocket extends IClientSocket, ISecureConnectingSocket {
    public function shouldVerifyPeer($flag=null);
    public function setCAFile($file);
    public function getCAFile();
    public function setCAPath($path);
    public function getCAPath();
    
    public function setMaxChainDepth($depth);
    public function getMaxChainDepth();
    
    public function shouldCapturePeerCertificate($flag=null);
    public function getPeerCertificate();
    public function shouldCapturePeerCertificateChain($flag=null);
    public function getPeerCertificateChain();
}

interface ISequenceClientSocket extends IClientSocket, ISequenceSocket {
    
}

interface IDatagramClientSocket extends IClientSocket, IDatagramSocket {

}

interface IRawClientSocket extends IClientSocket, IRawSocket {
    
}