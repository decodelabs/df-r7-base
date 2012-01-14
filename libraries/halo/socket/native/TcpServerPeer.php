<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\native;

use df;
use df\core;
use df\halo;

class TcpServerPeer extends halo\socket\ServerPeer implements halo\socket\ISequenceServerPeerSocket {
    
    protected $_useSeqPackets = false;
    
    protected static function _populateOptions() {
        return array();
    }
    
    public function __construct(halo\socket\IServerSocket $parent, $socket, $address) {
        parent::__construct($parent, $socket, $address);
        
        @socket_set_nonblock($this->_socket);
        $this->_applyOptions();
    }
    
    public function getImplementationName() {
        return 'native';
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
    
    
// Operation
    public function checkConnection() {
        if(!is_resource($this->_socket)) {
            return false;
        }
        
        return socket_recv($this->_socket, $data, 1, MSG_PEEK) !== 0;
    }
    
    
    protected function _peekChunk($length) {
        if(!socket_recv($this->_socket, $output, $length, MSG_PEEK)) {
            return false;
        }
        
        return $output;
    }
    
    protected function _readChunk($length) {
        if(!socket_recv($this->_socket, $output, $length, MSG_DONTWAIT)) {
            return false;
        }
        
        return $output;
    }
    
    protected function _writeChunk($data) {
        $output = socket_send($this->_socket, $data, strlen($data), 0);
        
        if($output === false
        || $output === 0) {
            return false;
        }
        
        return $output;
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