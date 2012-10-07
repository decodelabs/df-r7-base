<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\streams;

use df;
use df\core;
use df\halo;


// Client
class Tcp_Client extends halo\socket\Client implements halo\socket\ISequenceClientSocket, halo\socket\ISecureClientSocket {
    
    use halo\socket\TSequenceClientSocket;
    use halo\socket\TSecureClientSocket;
    use TStreams;
    use TStreams_IoSocket;

    protected static $_defaultOptions = array(
        'oobInline' => false
    );
    
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
            $socket = stream_socket_client(
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
        
        @stream_set_blocking($socket, false);
        return $socket;
    }

    protected function _connectPair() {
        switch($this->_address->getSocketDomain()) {
            case 'inet':
                $domain = STREAM_PF_INET;
                break;

            case 'inet6':
                $domain = STREAM_PF_INET6;
                break;

            case 'unix':
                $domain = STREAM_PF_UNIX;
                break;
        }

        return stream_socket_pair(
            $domain,
            $this->_useSeqPackets ? STREAM_SOCK_SEQPACKET : STREAM_SOCK_STREAM,
            STREAM_IPPROTO_TCP
        );
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
}


// Server
class Tcp_Server extends halo\socket\Server implements halo\socket\ISequenceServerSocket, halo\socket\ISecureServerSocket {
    
    use halo\socket\TSecureServerSocket;
    use halo\socket\TSequenceServerSocket;
    use TStreams;

    protected static $_defaultOptions = array(
        'oobInline' => false
    );
    
    protected static function _populateOptions() {
        return array_merge(parent::_populateOptions(), self::$_defaultOptions);
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
}



// Server peer
class Tcp_ServerPeer extends halo\socket\ServerPeer implements halo\socket\ISequenceServerPeerSocket, halo\socket\ISecureServerPeerSocket {

    use halo\socket\TSequenceServerPeerSocket;
    use halo\socket\TSecureServerPeerSocket;
    use TStreams;
    use TStreams_IoSocket;
    
    protected static function _populateOptions() {
        return array();
    }
    
    public function __construct(halo\socket\IServerSocket $parent, $socket, $address) {
        parent::__construct($parent, $socket, $address);
        
        stream_set_blocking($this->_socket, false);
        
        $this->_isSecure = $parent->isSecure();
        $this->_secureTransport = $parent->getSecureTransport();
    }
    
    
// Operation
    public function checkConnection() {
        if(!is_resource($this->_socket)) {
            return false;
        } 
        
        //stream_socket_recvfrom($this->_socket, 1, STREAM_PEEK);
        return !feof($this->_socket);
    }
}