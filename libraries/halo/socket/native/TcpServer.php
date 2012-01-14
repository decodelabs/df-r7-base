<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\native;

use df;
use df\core;
use df\halo;

class TcpServer extends halo\socket\Server implements halo\socket\ISequenceServerSocket {
    
    protected static $_defaultOptions = array(
        'oobInline' => false
    );
    
    protected $_useSeqPackets = false;
    
    protected static function _populateOptions() {
        return array_merge(parent::_populateOptions(), self::$_defaultOptions);
    }
    
    public function getImplementationName() {
        return 'native';
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
    
    
// Operation
    protected function _startListening() {
        $this->_socket = @socket_create(
            $this->_address->getIp()->isV6() ? AF_INET6 : AF_INET,
            $this->_useSeqPackets ? SOCK_SEQPACKET : SOCK_STREAM,
            getprotobyname('tcp')
        );
        
        if(!is_resource($this->_socket)) {
            throw new halo\socket\ConnectionException(
                'Could not create socket on '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }
        
        
        $this->_applyOptions();
        
        
        if(!@socket_bind($this->_socket, $this->_address->getIp()->toString(), $this->_address->getPort())) {
            throw new halo\socket\ConnectionException(
                'Could not bind server address '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }
        
        if(!@socket_listen($this->_socket, $this->getConnectionQueueSize())) {
            throw new halo\socket\ConnectionException(
                'Could not listen on '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }
    }
    
    protected function _applyOptions() {
        foreach($this->_options as $key => $value) {
            if($value === null) {
                continue;
            }
            
            switch($key) {
                case 'sendBufferSize':
                    $key = SO_SNDBUF;
                    $value = (int)$value;
                    break;
                    
                case 'receiveBufferSize':
                    $key = SO_RCVBUF;
                    $value = (int)$value;
                    break;
                    
                case 'sendLowWaterMark':
                    $key = SO_SNDLOWAT;
                    $value = (int)$value;
                    break;
                    
                case 'receiveLowWaterMark':
                    $key = SO_RCVLOWAT;
                    $value = (int)$value;
                    break;
                    
                case 'sendTimeout':
                    $key = SO_SNDTIMEO;
                    $value = array('sec' => 0, 'usec' => $value * 1000);
                    break;
                    
                case 'receiveTimeout':
                    $key = SO_RCVTIMEO;
                    $value = array('sec' => 0, 'usec' => $value * 1000);
                    break;
                    
                case 'reuseAddress':
                    $key = SO_REUSEADDR;
                    $value = (bool)$value;
                    break;
                    
                case 'oobInline':
                    $key = SO_OOBINLINE;
                    $value = (bool)$value;
                    break;
                    
                default:
                    continue 2;
            }
            
            @socket_set_option($this->_socket, SOL_SOCKET, $key, $value);
        }
    }
    
    protected function _acceptSequencePeer() {
        $output = @socket_accept($this->_socket);
        
        if($output) {
            @socket_set_nonblock($output);
        }
        
        return $output;
    }
    
    protected function _getPeerAddress($socket) {
        if(!@socket_getpeername($socket, $address, $port)) {
            throw new halo\socket\CreationException(
                'Could not get peer name - '.$this->_getLastErrorMessage()
            );
        }
        
        if($this->_address->getIp()->isV6()) {
            $address = '['.$address.']';
        }
        
        return $this->_address->getScheme().'://'.$address.':'.$port;
    }
    
    public function checkConnection() {
        return is_resource($this->_socket)
            && (@socket_getsockname($this->_socket, $address) !== false);
    }
    
    
    protected function _shutdownReading() {
        return @socket_shutdown($this->_socket, 0);
    }
    
    protected function _shutdownWriting() {
        return @socket_shutdown($this->_socket, 1);
    }
    
    protected function _closeSocket() {
        return @socket_close($this->_socket);
    }
    
    protected function _getLastErrorMessage() {
        return socket_strerror(socket_last_error());
    }
}