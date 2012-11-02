<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\streams;

use df;
use df\core;
use df\halo;

    
trait TStreams {

    private $_lastError = '';

    public function getImplementationName() {
        return 'streams';
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

    protected function _setBlocking($flag) {
        @stream_set_blocking($this->_socket, $flag);
    }

    protected function _enableSecureTransport() {
        if(null === ($id = $this->_getSecureTransportId($this->_secureTransport))) {
            return;
        }

        stream_set_blocking($this->_socket, true);
        stream_socket_enable_crypto($this->_socket, true, $id);
        stream_set_blocking($this->_socket, $this->_shouldBlock);
    }

    protected function _disableSecureTransport() {
        if(null === ($id = $this->_getSecureTransportId($this->_secureTransport))) {
            return;
        }

        stream_set_blocking($this->_socket, true);
        stream_socket_enable_crypto($this->_socket, false, $id);
        stream_set_blocking($this->_socket, $this->_shouldBlock);
    }

    protected function _getSecureTransportId($name) {
        switch($name) {
            case 'ssl':
                return STREAM_CRYPTO_METHOD_SSLv23_CLIENT;

            case 'sslv2':
                return STREAM_CRYPTO_METHOD_SSLv2_CLIENT;

            case 'sslv3':
                return STREAM_CRYPTO_METHOD_SSLv3_CLIENT;

            case 'tls':
                return STREAM_CRYPTO_METHOD_TLS_CLIENT;

            default:
                return null;
        }
    }
}

trait TStreams_IoSocket {

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

    protected function _readLine() {
        try {
            $output = fgets($this->_socket);
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
}