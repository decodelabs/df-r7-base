<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link;

use df;
use df\core;
use df\halo;
use df\link;

trait TPeer {
    
    protected $_readChunkSize = 16384;
    protected $_writeChunkSize = 8192;
    
    protected $_sessions = [];
    protected $_sessionCount = 0;

    protected $_maxWriteRetries = 10;
    
    
// Protocol
    public function getProtocolDisposition() {
        return static::PROTOCOL_DISPOSITION;
    }
    
    
// Registration
    protected function _registerSession(ISession $session) {
        $session->getSocket()->setSessionId(++$this->_sessionCount);
        $this->_sessions[$session->getId()] = $session;
        $this->_onSessionStart($session);
        
        return $session;
    }
    
    protected function _unregisterSession(ISession $session) {
        return $this->_unregisterSessionBySocket($session->getSocket());
    }
    
    protected function _unregisterSessionBySocket(link\socket\ISocket $socket) {
        $id = $socket->getId();
        
        if(isset($this->_sessions[$id])) {
            $this->_onSessionEnd($this->_sessions[$id]);
        }
        
        $socket->close();
        
        unset($this->_sessions[$id]);
        $this->events->removeSocket($socket);
    }
    
    
// Events
    protected $_reads = 0;

    protected function _onSocketDataAvailable($socket, $binding, $session) {
        $result = null;


        // If in write / listen mode, peer is responding early, we don't need to write any more
        if($session->getWriteState() == link\IIoState::WRITE_LISTEN) {
            $this->events->freezeSocketWrite($socket);
        }

        $this->_reads++;
        
        // Read from socket
        $data = '';
        $endRead = false;

        while(true) {
            $chunk = $socket->readChunk($this->_readChunkSize);

            if($chunk === false) {
                $endRead = true;
                break;
            } else if($chunk === '') {
                break;
            }

            $data .= $chunk;
        }

        if(strlen($data)) {
            // Add data to session
            $session->readBuffer .= $data;
            
            // Allow implementation to do something with it
            $result = $this->_handleReadBuffer($session, $data);
        } else if(!$socket->checkConnection()) {
            $endRead = true;
        }

        if($endRead) { // Peer has shutdown writing
            // Remove binding
            $binding->destroy();
            $this->_onPeerShutdownWriting($session);
            
            if(!$this->events->countSocketBindings($socket)) {
                $this->_unregisterSession($session);
                return;
            }
            
            if(!$result) {
                $result = IIoState::WRITE;
            }
        }
        
        
        switch($result) {
            case IIoState::END:
                // Implementation has finished, end session
                $session->readBuffer = false;
                $this->_unregisterSessionBySocket($socket);
                break;
                
            case IIoState::OPEN_WRITE:
            case IIoState::WRITE:
                
                if($result == IIoState::OPEN_WRITE) {
                    // Freeze reading, go to write mode
                    $binding->freeze();
                } else {
                    // Shutdown reading, go to write mode
                    $binding->destroy();
                    $socket->shutdownReading();
                }
                
                $session->readBuffer = false;
                
                try {
                    $this->events->unfreezeSocketWrite($socket);
                } catch(halo\event\BindException $e) {
                    $this->_unregisterSessionBySocket($socket);
                }
                
                break;
            
            case IIoState::READ:
            case IIoState::READ_LISTEN:
            case IIoState::OPEN_READ:
            default:
                break;
        }
        
        return;
    }
    
    protected function _onSocketConnectionWaiting($socket, $binding, $session) {
        if(!strlen($session->writeBuffer)) { // There's nothing to write
            switch($state = $session->getWriteState()) {
                case IIoState::END:
                    // Session has ended
                    $this->_unregisterSessionBySocket($socket);
                    return;
                
                case IIoState::OPEN_READ:
                case IIoState::READ:
                    
                    if($state == IIoState::OPEN_READ) {
                        // Freeze writing, go to read mode
                        $binding->freeze();
                    } else {
                        // Shutdown writing, go to read mode
                        $binding->destroy();
                        $socket->shutdownWriting();
                    }
                    
                    try {
                        $this->events->unfreezeSocketRead($socket);
                    } catch(halo\event\BindException $e) {
                        $this->_unregisterSessionBySocket($socket);
                    }

                    return;
                    
                case IIoState::WRITE:
                case IIoState::WRITE_LISTEN:
                case IIoState::OPEN_WRITE:
                default:
                    // Allow implementation to write to the buffer
                    $session->setWriteState($newState = $this->_handleWriteBuffer($session));

                    if($state === IIoState::WRITE_LISTEN || $newState === IIoState::WRITE_LISTEN) {
                        try {
                            $this->events->unfreezeSocketRead($socket);
                        } catch(halo\event\BindException $e) {
                            $this->_unregisterSessionBySocket($socket);
                        }
                    }

                    break;
            }
        }
        
        // Split into chunks
        $data = substr($session->writeBuffer, 0, $this->_writeChunkSize);

        // Write to socket
        $result = $socket->writeChunk($data);

        if($result) {
            // Remove chunk from buffer
            $session->writeBuffer = substr($session->writeBuffer, $result);
            $session->writeRetries = 0;
        } else {
            usleep(500000);
            $session->writeRetries++;
            //core\debug()->warning('Retrying write: '.strlen($data));

            if($session->writeRetries >= $this->_maxWriteRetries) {
                // Peer has stopped reading
                $session->writeRetries = 0;
                $binding->destroy();
                $socket->shutdownWriting();
                
                try {
                    $this->events->unfreezeSocketRead($socket);
                } catch(halo\event\BindException $e) {
                    $this->_unregisterSessionBySocket($socket);
                }
                
                return;
            }
        }
    }

// Event stubs
    protected function _onSessionStart(ISession $session) {}
    protected function _onSessionEnd(ISession $session) {}
    
    protected function _handleReadBuffer(ISession $session, $data) {}
    protected function _handleWriteBuffer(ISession $session) {}
    
    protected function _onPeerShutdownWriting(ISession $session) {}
}





// CLIENT
trait TPeer_Client {
    
    use TPeer;
    
    public function run() {
        if($this->isRunning()) {
            return $this;
        }
        
        $this->_createInitialSessions();
        
        if(empty($this->_sessions)) {
            throw new RuntimeException(
                'No client sessions have been opened'
            );
        }
        
        $this->getEventDispatcher();
        
        foreach($this->_sessions as $session) {
            $this->_dispatchSession($session);
        }
        
        $this->events->listen();
    }
    
    abstract protected function _createInitialSessions();
    
    protected function _dispatchSession(ISession $session) {
        $socket = $session->getSocket();
        $socket->connect();

        switch($this->getProtocolDisposition()) {
            case IClient::PEER_FIRST:
                $this->events
                    ->bindSocketRead(
                        $socket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketDataAvailable'], 
                            [$session]
                        )
                    )
                    ->bindFrozenSocketWrite(
                        $socket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketConnectionWaiting'], 
                            [$session]
                        )
                    );
                break;
                
            case IClient::CLIENT_FIRST:
                $this->events
                    ->bindSocketWrite(
                        $socket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketConnectionWaiting'], 
                            [$session]
                        )
                    )
                    ->bindFrozenSocketRead(
                        $socket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketDataAvailable'], 
                            [$session]
                        )
                    );
                break;
                
            case IClient::PEER_STREAM:
                core\stub('Unable to handle peer streams');
                break;
                
            case IClient::CLIENT_STREAM:
                core\stub('Unable to handle client streams');
                break;
                
            case IClient::DUPLEX_STREAM:
                core\stub('Unable to handle duplex streams');
                break;
                
            default:
                core\stub('Unknown protocol disposition');
        }
    }
}






// SERVER
trait TPeer_Server {
    
    use TPeer;
    
    protected $_masterSockets = [];
    
    protected function _setup() {
        $this->_setupPeerServer();
    }

    protected function _setupPeerServer() {
        $this->getEventDispatcher();
        
        // Heartbeat
        /*
        $dispatcher->bindTimer('heartbeat', 1, function() {
            echo 'Heartbeat'."\n";
        });
        */

        $this->_createMasterSockets();
        
        // Accept
        foreach($this->_masterSockets as $socket) {
            $socket->listen();

            $this->events->bindSocketRead(
                $socket,
                [$this, '_onSocketAcceptRequest']
            );
        }
    }
    
    protected function _teardown() {
        $this->_teardownPeerServer();
    }

    protected function _teardownPeerServer() {
        foreach($this->_sessions as $session) {
            $this->_unregisterSession($session);
        }
        
        foreach($this->_masterSockets as $id => $socket) {
            $this->events->removeSocket($socket);
            $socket->close();
            unset($this->_masterSockets[$id]);
        }

        //$this->removeTimer('heartbeat');
    }
    
    abstract protected function _createMasterSockets();
    abstract protected function _createSessionFromSocket(link\socket\IServerPeerSocket $socket);
    
    protected function _registerMasterSocket(link\socket\IServerSocket $socket) {
        $this->_masterSockets[$socket->getId()] = $socket;
        return $this;
    }
    
    
    
// Events
    protected function _onSocketAcceptRequest($handler, $binding) {
        $masterSocket = $handler->getSocket();
        
        if(!$this->_canAccept($masterSocket)) {
            // TODO: Freeze event binding and reinstate on a timeout
            return;
        }
        
        $peerSocket = $masterSocket->accept();
        $session = $this->_createSessionFromSocket($peerSocket);
        $this->_registerSession($session);
        
        switch($this->getProtocolDisposition()) {
            case IServer::SERVER_FIRST:
                $this->events
                    ->bindSocketWrite(
                        $peerSocket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketConnectionWaiting'], 
                            [$session]
                        )
                    )
                    ->bindFrozenSocketRead(
                        $peerSocket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketDataAvailable'], 
                            [$session]
                        )
                    );
                break;
                
            case IServer::PEER_FIRST:
                $this->events
                    ->bindSocketRead(
                        $peerSocket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketDataAvailable'], 
                            [$session]
                        )
                    )
                    ->bindFrozenSocketWrite(
                        $peerSocket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketConnectionWaiting'], 
                            [$session]
                        )
                    );
                break;
                
            case IServer::SERVER_STREAM:
                core\stub('Unable to handle server streams');

                $this->events
                    ->bindSocketWrite(
                        $peerSocket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketStreamConnectionWaiting']
                        )
                    );
                break;
                
            case IServer::PEER_STREAM:
                core\stub('Unable to handle peer streams');

                $this->events
                    ->bindSocketRead(
                        $peerSocket,
                        core\lang\Callback::factory(
                            [$this, '_onSocketStreamDataAvailable']
                        )
                    );

                break;
                
            case IServer::DUPLEX_STREAM:
                core\stub('Unable to handle duplex streams');
                break;
            
            default:
                core\stub('Unknown protocol disposition');
        }
            
        //echo 'Accept complete - '.$peerSocket->getSessionId()."\n";
    }
    
    
    
// Event stubs
    protected function _canAccept(link\socket\IServerSocket $socket) { return true; }
}




// SESSION
trait TPeer_Session {

    public $readBuffer = '';
    public $writeBuffer = '';
    public $writeRetries = 0;
    
    protected $_writeState = null;
    protected $_socket;
    protected $_store = [];
    
    public function __construct(link\socket\ISocket $socket) {
        $this->_socket = $socket;
    }
    
    public function getId() {
        return $this->_socket->getId();
    }
    
    public function getSocket() {
        return $this->_socket;
    }
    
    public function setWriteState($state) {
        $this->_writeState = $state;
        return $this;
    }
    
    public function getWriteState() {
        return $this->_writeState;
    }

// Store
    public function setStore($key, $value) {
        $this->_store[$key] = $value;
        return $this;
    }

    public function hasStore($key) {
        return isset($this->_store[$key]);
    }

    public function getStore($key, $default=null) {
        if(isset($this->_store[$key])) {
            return $this->_store[$key];
        }

        return $default;
    }

    public function removeStore($key) {
        unset($this->_store[$key]);
        return $this;
    }

    public function clearStore() {
        $this->_store = [];
        return $this;
    }
}


trait TPeer_RequestResponseSession {
    
    protected $_request;
    protected $_response;
    
    public function setRequest(ISessionRequest $request) {
        $this->_request = $request;
        return $this;
    }
    
    public function getRequest() {
        return $this->_request;
    }
    
    public function setResponse(ISessionResponse $response) {
        $this->_response = $response;
        return $this;
    }
    
    public function getResponse() {
        return $this->_response;
    }
    
}
    
    
trait TPeer_FileStreamSession {
    
    protected $_readFileStream;
    protected $_writeFileStream;
    
    public function setReadFileStream(core\io\IFilePointer $file) {
        $this->_readFileStream = $file;
        return $this;
    }
    
    public function getReadFileStream() {
        return $this->_readFileStream;
    }
    
    public function hasReadFileStream() {
        return $this->_readFileStream !== null;
    }

    public function setWriteFileStream(core\io\IFilePointer $file) {
        $this->_writeFileStream = $file;
        return $this;
    }
    
    public function getWriteFileStream() {
        return $this->_writeFileStream;
    }
    
    public function hasWriteFileStream() {
        return $this->_writeFileStream !== null;
    }
}


trait TPeer_ErrorCodeSession {
    
    protected $_errorCode;
    
    public function setErrorCode($code) {
        $this->_errorCode = $code;
        return $this;
    }
    
    public function getErrorCode() {
        return $this->_errorCode;
    }
    
    public function hasErrorCode() {
        return $this->_errorCode !== null;
    }
}


trait TPeer_CallbackSession {
    
    protected $_callback;
    
    public function setCallback($callback) {
        $this->_callback = core\lang\Callback::factory($callback);
        return $this;
    }
    
    public function getCallback() {
        return $this->_callback;
    }
}
