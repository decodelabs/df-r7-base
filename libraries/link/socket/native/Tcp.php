<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\socket\native;

use df;
use df\core;
use df\link;


// Server
class Tcp_Server extends link\socket\Server implements link\socket\ISequenceServerSocket {
    
    use link\socket\TSequenceServerSocket;
    use TNative;

    protected static $_defaultOptions = [
        'oobInline' => false
    ];
    
    protected static function _populateOptions() {
        return array_merge(parent::_populateOptions(), self::$_defaultOptions);
    }
    
    
    
// Operation
    protected function _startListening() {
        if($this->_address->getIp()->isV6()) {
            $domain = AF_INET6;
        } else {
            $domain = AF_INET;
        }
        
        if($this->_useSeqPackets) {
            $type = SOCK_SEQPACKET;
        } else {
            $type = SOCK_STREAM;
        }
        
        $this->_socket = @socket_create(
            $domain, $type, getprotobyname('tcp')
        );
        
        if(!is_resource($this->_socket)) {
            throw new link\socket\ConnectionException(
                'Could not create socket on '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }
        
        
        $this->_applyOptions();
        
        
        if(!@socket_bind($this->_socket, $this->_address->getIp()->toString(), $this->_address->getPort())) {
            throw new link\socket\ConnectionException(
                'Could not bind server address '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }
        
        if(!@socket_listen($this->_socket, $this->getConnectionQueueSize())) {
            throw new link\socket\ConnectionException(
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
                    $value = ['sec' => 0, 'usec' => $value * 1000];
                    break;
                    
                case 'receiveTimeout':
                    $key = SO_RCVTIMEO;
                    $value = ['sec' => 0, 'usec' => $value * 1000];
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
        return @socket_accept($this->_socket);
    }
    
    protected function _getPeerAddress($socket) {
        if(!@socket_getpeername($socket, $address, $port)) {
            throw new link\socket\CreationException(
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
}



// Server peer
class Tcp_ServerPeer extends link\socket\ServerPeer implements link\socket\ISequenceServerPeerSocket {
    
    use link\socket\TSequenceServerPeerSocket;
    use TNative;
    use TNative_IoSocket;

    protected static function _populateOptions() {
        return [];
    }
    
    public function __construct(link\socket\IServerSocket $parent, $socket, $address) {
        parent::__construct($parent, $socket, $address);
        $this->_applyOptions();
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
                    $value = ['sec' => 0, 'usec' => $value * 1000];
                    break;
                    
                case 'receiveTimeout':
                    $key = SO_RCVTIMEO;
                    $value = ['sec' => 0, 'usec' => $value * 1000];
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
    
    
    
// Operation
    public function checkConnection() {
        if(!is_resource($this->_socket)) {
            return false;
        }
        
        return socket_recv($this->_socket, $data, 1, MSG_PEEK) !== 0;
    }
}