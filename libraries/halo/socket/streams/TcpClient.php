<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\streams;

use df;
use df\core;
use df\halo;

class TcpClient extends halo\socket\Client implements halo\socket\ISequenceClientSocket, halo\socket\ISecureClientSocket {
    
    protected static $_defaultOptions = array(
        'oobInline' => false
    );
    
    protected $_useSeqPackets = false;
    protected $_isSecure = false;
    protected $_secureTransport = 'ssl';
    protected $_secureOptions = array(
        'allow_self_signed' => false,
        'CN_match' => null,
        'ciphers' => 'DEFAULT',
    
        'verify_peer' => false,
        'cafile' => null,
        'capath' => null,
        'verify_depth' => null,
    );
    
    private $_lastError = '';
    
    protected static function _populateOptions() {
        return array_merge(parent::_populateOptions(), self::$_defaultOptions);
    }
    
    public function getId() {
        if(!$this->_id) {
            if(!$this->_isConnected) {
                $this->connect();
                
                /*
                throw new halo\socket\RuntimeException(
                    'Client sockets cannot generate an ID before they are connected'
                );
                */
            }
            
            $this->_id = $this->_address.'|'.stream_socket_get_name($this->_socket, false);
        }
        
        return $this->_id;
    }
    
    public function getImplementationName() {
        return 'streams';
    }
    
// Options
    public function shouldUseSequencePackets($flag=null) {
        if($flag === null) {
            return $this->_useSeqPackets;
        }
        
        if($this->_socket) {
            throw new halo\socket\RuntimeException(
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
            throw new halo\socket\RuntimeException(
                'You cannot change out of band options once the client is connected'
            );
        }
        
        return $this->_setOption('oobInline', (bool)$flag);
    }
    
    
    
// Secure
    public function isSecure($flag=null) {
        if($flag !== null) {
            if($this->_socket) {
                throw new halo\socket\RuntimeException(
                    'Socket security cannot be changed once the socket is created'
                );
            }
            
            if($flag && !$this->canSecure()) {
                throw new halo\socket\RuntimeException(
                    'Open SSL does not appear to be available'
                );
            }
            
            $this->_isSecure = (bool)$flag;
        }
        
        return $this->_isSecure;
    }
    
    public function canSecure() {
        return extension_loaded('openssl');
    }
    
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
                throw new halo\socket\InvalidArgumentException(
                    $transport.' is not a supported secure transport'
                );
        }
        
        return $this;
    }
    
    public function getSecureTransport() {
        return $this->_secureTransport;
    }
    
    public function getSecureOptions() {
        return $this->_secureOptions;
    }
    
    
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
    
    protected function _setSecureOption($name, $value) {
        if($this->_socket) {
            throw new halo\socket\RuntimeException(
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
    
    
// Operation
    protected function _connectPeer() {
        $options = array();
        
        if($this->_isSecure) {
            $address = $this->_address->toString($this->getSecureTransport());
            $options['ssl'] = $this->_secureOptions;
        } else {
            $address = $this->_address->toString();
        }
        
        
        try {
            $context = stream_context_create($options);
            $this->_socket = stream_socket_client(
                $address,
                $errorNumber,
                $this->_lastError,
                $this->_getOption('connectionTimeout'),
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
                $context
            );
        } catch(\Exception $e) {
            throw new halo\socket\ConnectionException(
                'Could not connect client to '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }
        
        @stream_set_blocking($this->_socket, false);
    }
    
    public function checkConnection() {
        if(!is_resource($this->_socket)) {
            return false;
        } 
        
        if(stream_socket_get_name($this->_socket, true) === false) {
            return false;
        }
        
        //stream_socket_recvfrom($this->_socket, 1, STREAM_PEEK);
        return !feof($this->_socket);
    }
    
    protected function _peekChunk($length) {
        try {
            $output = stream_socket_recvfrom($this->_socket, $length, STREAM_PEEK);
        } catch(\Exception $e) {
            $this->_lastError = $e->getMessage();
            return false;
        }
        
        if($output === ''
        || $output === null
        || $output === false) {
            return false;
        }
        
        return $output;
    }
    
    protected function _readChunk($length) {
        try {
            $output = fread($this->_socket, $length);
        } catch(\Exception $e) {
            $this->_lastError = $e->getMessage();
            return false;
        }
        
        if($output === ''
        || $output === null
        || $output === false) {
            return false;
        }
        
        return $output;
    }
    
    protected function _writeChunk($data) {
        try {
            $output = fwrite($this->_socket, $data);
        } catch(\Exception $e) {
            $this->_lastError = $e->getMessage();
            return false;
        }
        
        if($output === false
        || $output === 0) {
            return false;
        }
        
        return $output;
    }

    protected function _shutdownReading() {
        try {
            $this->_lastError = '';
            return stream_socket_shutdown($this->_socket, STREAM_SHUT_RD);
        } catch(\Exception $e) {
            $this->_lastError = $e->getMessage();
            return false;
        }
    }
    
    protected function _shutdownWriting() {
        try {
            $this->_lastError = '';
            return stream_socket_shutdown($this->_socket, STREAM_SHUT_WR);
        } catch(\Exception $e) {
            $this->_lastError = $e->getMessage();
            return false;
        }
    }
    
    protected function _closeSocket() {
        $this->_isConnected = false;
        return @fclose($this->_socket);
    }
    
    protected function _getLastErrorMessage() {
        return $this->_lastError;
    }
}