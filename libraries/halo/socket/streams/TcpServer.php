<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\streams;

use df;
use df\core;
use df\halo;

class TcpServer extends halo\socket\Server implements halo\socket\ISequenceServerSocket, halo\socket\ISecureServerSocket {
    
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
    
        'local_cert' => null,
        'passphrase' => null,
        'SNI_enabled' => false,
    );
    
    private $_lastError = '';
    
    protected static function _populateOptions() {
        return array_merge(parent::_populateOptions(), self::$_defaultOptions);
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
    
    public function setConnectionQueueSize($size) {
        if($this->_isListening) {
            throw new halo\socket\RuntimeException(
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
     
    
    public function shouldSendOutOfBandDataInline($flag=null) {
        if($flag === null) {
            return $this->_getOption('oobInline');
        }
        
        return $this->_setOption('oobInline', (bool)$flag);
    }
    
    
// Secure
    // TODO: allow switching once connected
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
    protected function _startListening() {
        $options = array(
            'socket' => array(
                'backlog' => $this->getConnectionQueueSize()
            )
        );
        
        if($this->_isSecure) {
            $address = $this->_address->toString($this->getSecureTransport());
            
            $options['ssl'] = $this->_secureOptions;
        } else {
            $address = $this->_address->toString();
        }
        
        
        try {
            $context = stream_context_create($options);
            $this->_socket = @stream_socket_server(
                $address,
                $errorNumber,
                $this->_lastError,
                STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
                $context
            );
        } catch(\Exception $e) {
            throw new halo\socket\ConnectionException(
                'Could not create socket on '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }
    }
    
    protected function _acceptSequencePeer()  {
        try {
            $output = stream_socket_accept($this->_socket);
        } catch(\Exception $e) {
            $this->_lastError = $e->getMessage();
            return false;
        }
        
        @stream_set_blocking($this->_socket, false);
        return $output;
    }
    
    protected function _getPeerAddress($socket) {
        return $this->_address->getScheme().'://'.stream_socket_get_name($socket, true);
    }
    
   
    public function checkConnection() {
        return is_resource($this->_socket) 
            && (stream_socket_get_name($this->_socket, false) !== false);
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
        return @fclose($this->_socket);
    }
    
    protected function _getLastErrorMessage() {
        return $this->_lastError;
    }
}