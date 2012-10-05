<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\peer;

use df;
use df\core;
use df\halo;

trait TPeer {
    
    protected $_readChunkSize = 16384;
    protected $_writeChunkSize = 8192;
    
    protected $_dispatcher;
    protected $_sessions = array();
    protected $_sessionCount = 0;
    
    
// Dispatcher
    public function setDispatcher(halo\event\IDispatcher $dispatcher) {
        if($this->_dispatcher && $this->_dispatcher->isRunning()) {
            throw new RuntimeException(
                'You cannot change the dispatcher once the server has started'
            );
        }
        
        $this->_dispatcher = $dispatcher;
        return $this;
    }
    
    public function getDispatcher() {
        if(!$this->_dispatcher) {
            $this->_dispatcher = halo\event\DispatcherBase::factory();
        }
        
        return $this->_dispatcher;
    }
    
    public function isRunning() {
        return $this->_dispatcher && $this->_dispatcher->isRunning();
    }
    
    
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
    
    protected function _unregisterSessionBySocket(halo\socket\ISocket $socket) {
        $id = $socket->getId();
        
        if(isset($this->_sessions[$id])) {
            $this->_onSessionEnd($this->_sessions[$id]);
        }
        
        $socket->close();
        
        unset($this->_sessions[$id]);
        $this->_dispatcher->removeSocket($socket);
    }
    
    
// Events
    public function handleEvent(halo\event\IHandler $handler, halo\event\IBinding $binding) {
        $func = '_on'.ucfirst($handler->getScheme()).$binding->getName();
        $args = $binding->getArgs();
        array_unshift($args, $handler, $binding);
        
        if(!method_exists($this, $func)) {
            throw new RuntimeException('Missing event handler: '.$func);
        }
            
        call_user_func_array(array($this, $func), $args);
    }

    protected function _onSocketDataAvailable($handler, $binding, $session) {
        $socket = $handler->getSocket();
        
        // Read from socket
        $data = $socket->read($this->_readChunkSize);
        
        if($data === false) { // Peer has shutdown writing
            // Remove binding
            $handler->unbind($binding);
            $this->_onPeerShutdownWriting($session);
            
            if(!$handler->countBindings()) {
                $this->_unregisterSession($session);
                return;
            }
            
            $result = IIoState::WRITE;
        } else {
            // Add data to session
            $session->readBuffer .= $data;
            
            // Allow implementation to do something with it
            $result = $this->_handleReadBuffer($session, $data);
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
                    $handler->freeze($binding);
                } else {
                    // Shutdown reading, go to write mode
                    $handler->unbind($binding);
                    $socket->shutdownReading();
                }
                
                $session->readBuffer = false;
                
                try {
                    $handler->unfreeze($handler->getBinding($this, 'connectionWaiting', halo\event\WRITE));
                } catch(halo\event\BindException $e) {
                    $this->_unregisterSessionBySocket($socket);
                }
                
                break;
            
            case IIoState::BUFFER:
            default:
                break;
        }
        
        return;
    }
    
    protected function _onSocketConnectionWaiting($handler, $binding, $session) {
        $socket = $handler->getSocket();
        
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
                        $handler->freeze($binding);
                    } else {
                        // Shutdown writing, go to read mode
                        $handler->unbind($binding);
                        $socket->shutdownWriting();
                    }
                    
                    try {
                        $handler->unfreeze($handler->getBinding($this, 'dataAvailable', halo\event\READ));
                    } catch(halo\event\BindException $e) {
                        $this->_unregisterSessionBySocket($socket);
                    }
                    
                    return;
                    
                case IIoState::BUFFER:
                default:
                    // Allow implementation to write to the buffer
                    $session->setWriteState($this->_handleWriteBuffer($session));
                    break;
            }
        }
        
        // Split into chunks
        $data = substr($session->writeBuffer, 0, $this->_writeChunkSize);
        
        // Write to socket
        $written = $socket->write($data);
        
        if($written === false) {
            // Peer has stopped reading
            
            $handler->unbind($binding);
            $socket->shutdownWriting();
            
            try {
                $handler->unfreeze($handler->getBinding($this, 'dataAvailable', halo\event\READ));
            } catch(halo\event\BindException $e) {
                $this->_unregisterSessionBySocket($socket);
            }
            
            return;
        }
        
        // Put rest of chunks back into session buffer
        $session->writeBuffer = substr($session->writeBuffer, $written);
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
        if($this->_dispatcher && $this->_dispatcher->isRunning()) {
            return $this;
        }
        
        $this->_createInitialSessions();
        
        if(empty($this->_sessions)) {
            throw new RuntimeException(
                'No client sessions have been opened'
            );
        }
        
        $dispatcher = $this->getDispatcher();
        
        foreach($this->_sessions as $session) {
            $this->_dispatchSession($session);
        }
        
        $this->getDispatcher()->start();
    }
    
    abstract protected function _createInitialSessions();
    
    protected function _dispatchSession(ISession $session) {
        $socket = $session->getSocket();
        $socket->connect();
        
        $eventHandler = $this->_dispatcher->newSocketHandler($socket);
        
        switch($this->getProtocolDisposition()) {
            case IClient::PEER_FIRST:
                $eventHandler->bind($this, 'dataAvailable', true, [$session]);
                $eventHandler->freeze($eventHandler->bindWrite($this, 'connectionWaiting', true, [$session]));
                break;
                
            case IClient::CLIENT_FIRST:
                $eventHandler->bindWrite($this, 'connectionWaiting', true, [$session]);
                $eventHandler->freeze($eventHandler->bind($this, 'dataAvailable', true, [$session]));
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
    
    protected $_isStarted = false;
    protected $_masterSockets = array();
    
    public function start() {
        if($this->_isStarted) {
            return $this;
        }
        
        $dispatcher = $this->getDispatcher();
        
        if($dispatcher->isRunning()) {
            $dispatcher->stop();
        }
        
        $this->_setup();
        $this->_isStarted = true;
        
        $dispatcher->start();
    }
    
    public function stop() {
        if(!$this->_isStarted) {
            return $this;
        }
        
        $dispatcher = $this->getDispatcher();
        
        if($isRunning = $dispatcher->isRunning()) {
            $dispatcher->stop();
        }
        
        $this->_teardown();
        $this->_isStarted = false;
        
        if($isRunning && $dispatcher->countHandlers()) {
            $dispatcher->start();
        }
    }
    
    protected function _setup() {
        $dispatcher = $this->getDispatcher();
        
        // Heartbeat
        /*
        $dispatcher->newTimerHandler(core\time\Duration::factory(5))
            ->bind($this, 'heartbeat', true);
        */

        $this->_createMasterSockets();
        
        // Accept
        foreach($this->_masterSockets as $socket) {
            $socket->listen();
            
            $dispatcher->newSocketHandler($socket)
                ->bind($this, 'acceptRequest', true);
        }
    }
    
    protected function _teardown() {
        $dispatcher = $this->getDispatcher();
        
        foreach($this->_sessions as $session) {
            $this->_unregisterSession($session);
        }
        
        foreach($this->_masterSockets as $id => $socket) {
            $dispatcher->removeSocket($socket);
            $socket->close();
            unset($this->_masterSockets[$id]);
        }
        
        /*
        $heartbeat = $dispatcher->getTimerHandler(core\time\Duration::factory(5));
        $heartbeat->unbindAll($this);
        
        if(!$heartbeat->countBindings()) {
            $heartbeat->destroy();
        }
        */
    }
    
    abstract protected function _createMasterSockets();
    abstract protected function _createSessionFromSocket(halo\socket\IServerPeerSocket $socket);
    
    protected function _registerMasterSocket(halo\socket\IServerSocket $socket) {
        $this->_masterSockets[$socket->getId()] = $socket;
        return $this;
    }
    
    
    
// Events
    /*
    protected function _onTimerHeartbeat($handler, $binding) {
        if(!empty($this->_sessions)) {
            echo count($this->_sessions).' connections currently open'."\n";
        }
    }
    */
    
    
    protected function _onSocketAcceptRequest($handler, $binding) {
        $masterSocket = $handler->getSocket();
        
        if(!$this->_canAccept($masterSocket)) {
            // TODO: Freeze event binding and reinstate on a timeout
            return;
        }
        
        $peerSocket = $masterSocket->accept();
        $session = $this->_createSessionFromSocket($peerSocket);
        $this->_registerSession($session);
        
        $eventHandler = $this->_dispatcher->newSocketHandler($peerSocket);
        
        switch($this->getProtocolDisposition()) {
            case IServer::SERVER_FIRST:
                $eventHandler->bindWrite($this, 'connectionWaiting', true, [$session]);
                $eventHandler->freeze($eventHandler->bind($this, 'dataAvailable', true, [$session]));
                break;
                
            case IServer::PEER_FIRST:
                $eventHandler->bind($this, 'dataAvailable', true, [$session]);
                $eventHandler->freeze($eventHandler->bindWrite($this, 'connectionWaiting', true, [$session]));
                break;
                
            case IServer::SERVER_STREAM:
                core\stub('Unable to handle server streams');
                $eventHandler->bindWrite($this, 'streamConnectionWaiting', true);
                break;
                
            case IServer::PEER_STREAM:
                core\stub('Unable to handle peer streams');
                $eventHandler->bind($this, 'streamDataAvailable', true);
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
    protected function _canAccept(halo\socket\IServerSocket $socket) { return true; }
}




// SESSION
trait TPeer_Session {

    public $readBuffer = '';
    public $writeBuffer = '';
    
    protected $_writeState = IIoState::BUFFER;
    protected $_socket;
    
    public function __construct(halo\socket\ISocket $socket) {
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
    
    protected $_fileStream;
    
    public function setFileStream(core\io\file\IPointer $file) {
        $this->_fileStream = $file;
        return $this;
    }
    
    public function getFileStream() {
        return $this->_fileStream;
    }
    
    public function hasFileStream() {
        return $this->_fileStream !== null;
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
    
    public function setCallback(Callable $callback) {
        $this->_callback = $callback;
        return $this;
    }
    
    public function getCallback() {
        return $this->_callback;
    }
}
