<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\socket;

use df;
use df\core;
use df\link;

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
    public function isConnected();
    public function isActive();
    public function isReadingEnabled();
    public function isWritingEnabled();
    public function shouldBlock($flag=null);
    
    // Shutdown
    public function shutdownReading();
    public function shutdownWriting();
    public function close();
}

interface IConnectionOrientedSocket extends ISocket {
    public function checkConnection();
}

interface IIoSocket extends ISocket, core\io\IReader, core\io\IPeekReader, core\io\IWriter {}

trait TIoSocket {
    use core\io\TReader;
    use core\io\TPeekReader;
    use core\io\TWriter;
}



// Secure
interface ISecureSocket extends ISocket {
    public function isSecure();
    public function canSecure();
    public function getSecureTransport();
}

trait TSecureSocket {

    protected $_isSecure = false;
    protected $_secureTransport = 'ssl';

    public function isSecure() {
        return $this->_isSecure;
    }

    public function canSecure() {
        return extension_loaded('openssl');
    }

    public function getSecureTransport() {
        return $this->_secureTransport;
    }
}


interface ISecureConnectingSocket extends ISecureSocket {
    public function setSecureTransport($transport);
    public function getSecureOptions();
    public function shouldSecureOnConnect($flag=null);
    public function enableSecureTransport();
    public function disableSecureTransport();
    public function allowSelfSigned($flag=null);
    public function setCommonName($name);
    public function getCommonName();
    public function setCiphers($ciphers);
    public function getCiphers();
}

trait TSecureConnectingSocket {

    use TSecureSocket;
    
    /*
    protected $_secureOptions = [
        'allow_self_signed' => false,
        'CN_match' => null,
        'ciphers' => 'DEFAULT'
    ];
    */

    protected $_secureOnConnect = true;
    protected $_secureTransportEnabled = false;

    public function setSecureTransport($transport) {
        $transport = strtolower($transport);
        
        switch($transport) {
            case 'ssl':
            case 'sslv2':
            case 'sslv3':
            case 'tls':
                $this->_isSecure = true;
                $this->_secureTransport = $transport;
                break;
                
            case null:
                $this->_isSecure = false;
                $this->_secureTransport = 'ssl';
                break;
                
            default:
                throw new link\socket\InvalidArgumentException(
                    $transport.' is not a supported secure transport'
                );
        }
        
        return $this;
    }
    
    
    public function getSecureOptions() {
        return $this->_secureOptions;
    }
    
    public function shouldSecureOnConnect($flag=null) {
        if($flag !== null) {
            $this->_secureOnConnect = (bool)$flag;
            return $this;
        }

        return $this->_secureOnConnect;
    }

    public function enableSecureTransport() {
        if($this->_secureTransportEnabled) {
            return $this;
        }

        $this->_enableSecureTransport();
        $this->_secureTransportEnabled = true;

        return $this;
    }

    public function disableSecureTransport() {
        if(!$this->_secureTransportEnabled) {
            return $this;
        }

        $this->_disableSecureTransport();
        $this->_secureTransportEnabled = false;

        return $this;
    }

    abstract protected function _enableSecureTransport();
    abstract protected function _disableSecureTransport();
    
    public function allowSelfSigned($flag=null) {
        if($flag !== null) {
            return $this->_setSecureOption('allow_self_signed', (bool)$flag);
        }
        
        return $this->_getSecureOption('allow_self_signed');
    }
    
    public function setCommonName($name) {
        return $this->_setSecureOption('CN_match', $name);
    }
    
    public function getCommonName() {
        return $this->_getSecureOption('CN_match');
    }
    
    public function setCiphers($ciphers) {
        return $this->_setSecureOption('ciphers', $ciphers);
    }
    
    public function getCiphers() {
        return $this->_getSecureOption('ciphers');
    }
    
    protected function _setSecureOption($name, $value) {
        if($this->isConnected()) {
            throw new link\socket\RuntimeException(
                'You cannot change secure connection options once the socket has been created'
            );
        }
        
        $this->_secureOptions[$name] = $value;
        return $this;
    }
    
    protected function _getSecureOption($name) {
        if(isset($this->_secureOptions[$name])) {
            return $this->_secureOptions[$name];
        }
        
        return null;
    }
}





// Sequence
interface ISequenceSocket extends ISocket, IConnectionOrientedSocket {
    public function shouldUseSequencePackets($flag=null);
    public function shouldSendOutOfBandDataInline($flag=null);
}

trait TSequenceSocket {

    protected $_useSeqPackets = false;

    public function shouldUseSequencePackets($flag=null) {
        if($flag === null) {
            return $this->_useSeqPackets;
        }
        
        if($this->_socket) {
            throw new link\socket\RuntimeException(
                'You cannot set the socket type after it has been created'
            );
        }
        
        $this->_useSeqPackets = (bool)$flag;
        return $this;
    }

    public function shouldSendOutOfBandDataInline($flag=null) {
        if($flag === null) {
            return $this->_getOption('oobInline');
        }
        
        if($this->_isConnected) {
            throw new link\socket\RuntimeException(
                'You cannot change out of band options once the socket is connected'
            );
        }
        
        return $this->_setOption('oobInline', (bool)$flag);
    }
}


// Datagram
interface IDatagramSocket extends ISocket {
    
}

// Raw
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

trait TSecureServerSocket {

    use TSecureConnectingSocket;

    protected $_secureOptions = [
        'allow_self_signed' => false,
        'CN_match' => null,
        'ciphers' => 'DEFAULT',
    
        'local_cert' => null,
        'passphrase' => null,
        'SNI_enabled' => false,
    ];

    public function setLocalCertificate($file) {
        return $this->_setSecureOption('local_cert', $file);
    }
    
    public function getLocalCertificate() {
        return $this->_getSecureOption('local_cert');
    }
    
    
    public function setPassphrase($passphrase) {
        return $this->_setSecureOption('passphrase', $passphrase);
    }
    
    public function getPassphrase() {
        return $this->_getSecureOption('passphrase');
    }
    
    
    public function shouldUseSNI($flag=null) {
        if($flag !== null) {
            return $this->_setSecureOption('SNI_enabled', (bool)$flag);
        }
        
        return $this->_getSecureOption('SNI_enabled');
    }
    
    public function setSNIServerName($name) {
        return $this->_setSecureOption('SNI_server_name', $name);
    }
    
    public function getSNIServerName() {
        return $this->_getSecureOption('SNI_server_name');
    }
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

trait TSequenceServerSocket {

    use TSequenceSocket;

    public function setConnectionQueueSize($size) {
        if($this->isListening()) {
            throw new link\socket\RuntimeException(
                'Can\'t set connection queue size once the server is listening'
            );
        }
        
        return $this->_setOption('connectionQueueSize', (int)$size);
    }
    
    public function getConnectionQueueSize() {
        return $this->_getOption('connectionQueueSize');
    }
    
    
    public function shouldLingerOnClose($flag=null, $timeout=null) {
        if($flag === null) {
            return $this->_getOption('lingerOnClose');
        }
        
        $this->_setOption('lingerOnClose', (bool)$flag);
        
        if($timeout !== null) {
            $this->setLingerTimeout($timeout);
        }
        
        return $this;
    }
    
    public function setLingerTimeout($timeout) {
        return $this->_setOption('lingerTimeout', $timeout);
    }
    
    public function getLingerTimeout() {
        return $this->_getOption('lingerTimeout');
    }
}


interface IDatagramServerSocket extends IServerSocket, IDatagramSocket, IBroadcastableServerSocket {
    
}

interface IRawServerSocket extends IServerSocket, IRawSocket {
    
}



// Server peer
interface IServerPeerSocket extends ISocket, IIoSocket {}

interface ISecureServerPeerSocket extends IServerPeerSocket, ISecureSocket {}

trait TSecureServerPeerSocket {

    use TSecureSocket;
}

interface ISequenceServerPeerSocket extends IServerPeerSocket, ISequenceSocket {}

trait TSequenceServerPeerSocket {

    use TSequenceSocket;
}

interface IDatagramServerPeerSocket extends IServerPeerSocket, IDatagramSocket {}

interface IRawServerPeerSocket extends IServerPeerSocket, IRawSocket {}




// Client
interface IClientSocket extends ISocket, IIoSocket {
    // Options
    public function setConnectionTimeout($timeout);
    public function getConnectionTimeout();
    
    // Operation
    public function connect();
    public function connectPair();
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

trait TSecureClientSocket {

    use TSecureConnectingSocket;

    protected $_secureOptions = [
        'allow_self_signed' => false,
        'CN_match' => null,
        'ciphers' => 'DEFAULT',
    
        'verify_peer' => false,
        'cafile' => null,
        'capath' => null,
        'verify_depth' => null,
    ];

    public function shouldVerifyPeer($flag=null) {
        if($flag !== null) {
            return $this->_setSecureOption('verify_peer', (bool)$flag);
        }
        
        return $this->_getSecureOption('verify_peer');
    }
    
    public function setCAFile($file) {
        return $this->_setSecureOption('cafile', $file);
    }
    
    public function getCAFile() {
        return $this->_getSecureOption('cafile');
    }
    
    public function setCAPath($path) {
        return $this->_setSecureOption('capath', $path);
    }
    
    public function getCAPath() {
        return $this->_getSecureOption('capath');
    }
    
    
    public function setMaxChainDepth($depth) {
        return $this->_setSecureOption('verify_depth', (int)$depth);
    }
    
    public function getMaxChainDepth() {
        return $this->_getSecureOption('verify_depth');
    }
    
    
    public function shouldCapturePeerCertificate($flag=null) {
        if($flag !== null) {
            return $this->_setSecureOption('capture_peer_cert', (bool)$flag);
        }
        
        return $this->_getSecureOption('capture_peer_cert');
    }
    
    public function getPeerCertificate() {
        return $this->_getSecureOption('peer_certificate');
    }
    
    public function shouldCapturePeerCertificateChain($flag=null) {
        if($flag !== null) {
            return $this->_setSecureOption('capture_peer_cert_chain', (bool)$flag);
        }
        
        return $this->_getSecureOption('capture_peer_cert_chain');
    }
    
    public function getPeerCertificateChain() {
        return $this->_getSecureOption('peer_certificate_chain');
    }
}


interface ISequenceClientSocket extends IClientSocket, ISequenceSocket {}

trait TSequenceClientSocket {

    use TSequenceSocket;
}

interface IDatagramClientSocket extends IClientSocket, IDatagramSocket {}
interface IRawClientSocket extends IClientSocket, IRawSocket {}
