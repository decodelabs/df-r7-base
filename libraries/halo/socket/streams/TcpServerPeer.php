<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\streams;

use df;
use df\core;
use df\halo;

class TcpServerPeer extends halo\socket\ServerPeer implements halo\socket\ISequenceServerPeerSocket, halo\socket\ISecureServerPeerSocket {
    
    protected $_useSeqPackets = false;
    protected $_isSecure = false;
    protected $_secureTransport = 'ssl';
    
    protected static function _populateOptions() {
        return array();
    }
    
    public function __construct(halo\socket\IServerSocket $parent, $socket, $address) {
        parent::__construct($parent, $socket, $address);
        
        stream_set_blocking($this->_socket, false);
        
        $this->_isSecure = $parent->isSecure();
        $this->_secureTransport = $parent->getSecureTransport();
    }
    
    public function getImplementationName() {
        return 'streams';
    }
    

// Options
    public function shouldUseSequencePackets($flag=null) {
        if($flag === null) {
            return $this->_useSeqPackets;
        }
        
        throw new halo\socket\RuntimeException(
            'You cannot set the socket type after it has been created'
        );
    }
    
    public function shouldSendOutOfBandDataInline($flag=null) {
        if($flag === null) {
            return $this->_getOption('oobInline');
        }
        
        throw new halo\socket\RuntimeException(
            'You cannot change out of band options once the client is connected'
        );
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
    
    public function getSecureTransport() {
        return $this->_secureTransport;
    }
    
    
// Operation
    public function checkConnection() {
        if(!is_resource($this->_socket)) {
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
        return @fclose($this->_socket);
    }
    
    protected function _getLastErrorMessage() {
        return $this->_lastError;
    }
}