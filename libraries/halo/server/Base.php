<?php

namespace df\halo\server;

use df\core;
use df\halo;

abstract class Base implements IServer {
    
    const SERVER_FIRST = 1;
    const PEER_FIRST = 2;
    const SERVER_STREAM = 3;
    const PEER_STREAM = 4;
    const DUPLEX_STREAM = 5;
    
    const BUFFER = null;
    const WRITE = 1;
    const OPEN_WRITE = 2;
    const READ = 3;
    const OPEN_READ = 4;
    const END = 5;
    
    const PROTOCOL_DISPOSITION = self::PEER_FIRST;
    
    protected $_isStarted = false;
    protected $_masterSockets = array();
    protected $_sessions = array();
    protected $_dispatcher;
    
    protected $_requests = 0;
    
    protected $_readChunkSize = 16384;
    protected $_writeChunkSize = 8192;
    
    public function __construct() {
        
    }
    
    public function setDispatcher(halo\event\IDispatcher $dispatcher) {
        if($this->_isStarted) {
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
    
    public function getProtocolDisposition() {
        return static::PROTOCOL_DISPOSITION;
    }
    
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
        $dispatcher->newTimerHandler(core\time\Duration::factory(5))
            ->bind($this, 'heartbeat', true);
            
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
        
        $heartbeat = $dispatcher->getTimerHandler(core\time\Duration::factory(5));
        $heartbeat->unbindAll($this);
        
        if(!$heartbeat->countBindings()) {
            $heartbeat->destroy();
        }
    }
    
    abstract protected function _createMasterSockets();
    
    protected function _registerMasterSocket(halo\socket\IServerSocket $socket) {
        $this->_masterSockets[$socket->getId()] = $socket;
        return $this;
    }
    
    
    protected function _registerSession(halo\socket\IServerPeerSocket $socket) {
        $socket->setSessionId(++$this->_requests);
        
        $session = $this->_createSession($socket);
        $this->_sessions[$session->getId()] = $session;
        $this->_onSessionStart($session);
        
        return $session;
    }
    
    protected function _createSession(halo\socket\IServerPeerSocket $socket) {
        return new Session($socket);
    }
    
    
    protected function _unregisterSession(ISession $session) {
        return $this->_unregisterSessionBySocket($session->getSocket());
    }
    
    protected function _unregisterSessionBySocket(halo\socket\IServerPeerSocket $socket) {
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
            echo 'Missing event handler: '.$func."\n";
        } else {
            call_user_func_array(array($this, $func), $args);
        }
    }
    
    
    protected function _onTimerHeartbeat($handler, $binding) {
        if(!empty($this->_sessions)) {
            echo count($this->_sessions).' connections currently open'."\n";
        }
        
        echo 'Served '.$this->_requests.' requests'."\n";
        
        /*
        if($this->_requests > 100) {
            $this->stop();
        }
        */
    }
    
    
    protected function _onSocketAcceptRequest($handler, $binding) {
        $socket = $handler->getSocket();
        
        if(!$this->_canAccept($socket)) {
            // TODO: Freeze event binding and reinstate on a timeout
            return;
        }
        
        $connection = $socket->accept();
        $session = $this->_registerSession($connection);
        
        $eventHandler = $this->_dispatcher->newSocketHandler($connection);
        
        switch($this->getProtocolDisposition()) {
            case self::SERVER_FIRST:
                $eventHandler->bindWrite($this, 'connectionWaiting', true, array($session));
                $eventHandler->freeze($eventHandler->bind($this, 'connectionDataAvailable', true, array($session)));
                break;
                
            case self::SERVER_STREAM:
                core\stub();
                $eventHandler->bindWrite($this, 'streamConnectionWaiting', true);
                break;
                
            case self::PEER_FIRST:
                $eventHandler->bind($this, 'connectionDataAvailable', true, array($session));
                $eventHandler->freeze($eventHandler->bindWrite($this, 'connectionWaiting', true, array($session)));
                break;
                
            case self::PEER_STREAM:
                core\stub();
                $eventHandler->bind($this, 'streamConnectionDataAvailable', true);
                break;
                
            case self::DUPLEX_STREAM:
                core\stub();
                break;
            
            default:
                core\stub();
        }
            
        //echo 'Accept complete - '.$connection->getSessionId()."\n";
    }
    
    protected function _onSocketConnectionDataAvailable($handler, $binding, $session) {
        $connection = $handler->getSocket();
        $data = $connection->read($this->_readChunkSize);
        
        
        if($data === false) {
            // Peer has shutdown writing
            
            $handler->unbind($binding);
            $this->_onPeerShutdownWriting($session);
            
            if(!$handler->countBindings()) {
                $this->_unregisterSession($session);
                return;
            }
            
            $result = self::WRITE;
        } else {
            $session->readBuffer .= $data;
            $result = $this->_handleReadBuffer($session, $data);
        }
        
        
        
        
        switch($result) {
            case self::END:
                $this->_unregisterSessionBySocket($connection);
                break;
                
            case self::OPEN_WRITE:
                $handler->freeze($binding);
                
                try {
                    $handler->unfreeze($handler->getBinding($this, 'connectionWaiting', halo\event\WRITE));
                } catch(halo\event\BindException $e) {
                    $this->_unregisterSessionBySocket($connection);
                }
                
                break;
                
            case self::WRITE:
                $handler->unbind($binding);
                $connection->shutdownReading();
                
                try {
                    $handler->unfreeze($handler->getBinding($this, 'connectionWaiting', halo\event\WRITE));
                } catch(halo\event\BindException $e) {
                    $this->_unregisterSessionBySocket($connection);
                }
                
                break;
            
            case self::BUFFER:
            default:
                break;
        }
        
        return;
    }
    
    protected function _onSocketConnectionWaiting($handler, $binding, $session) {
        $connection = $handler->getSocket();
        
        if(!strlen($session->writeBuffer)) {
            switch($session->getWriteState()) {
                case self::END:
                    $this->_unregisterSessionBySocket($connection);
                    return;
                
                case self::OPEN_READ:
                    $handler->freeze($binding);
                
                    try {
                        $handler->unfreeze($handler->getBinding($this, 'connectionDataAvailable', halo\event\READ));
                    } catch(halo\event\BindException $e) {
                        $this->_unregisterSessionBySocket($connection);
                    }
                    
                    return;
                    
                case self::READ:
                    $handler->unbind($binding);
                    $connection->shutdownWriting();
                    
                    try {
                        $handler->unfreeze($handler->getBinding($this, 'connectionDataAvailable', halo\event\READ));
                    } catch(halo\event\BindException $e) {
                        $this->_unregisterSessionBySocket($connection);
                    }
                    
                    return;
                    
                case self::BUFFER:
                default:
                    $session->setWriteState($this->_handleWriteBuffer($session));
                    break;
            }
        }
        
        $data = substr($session->writeBuffer, 0, $this->_writeChunkSize);
        $written = $connection->write($data);
        
        if($written === false) {
            // Peer has stopped reading
            
            $handler->unbind($binding);
            $connection->shutdownWriting();
            
            try {
                $handler->unfreeze($handler->getBinding($this, 'connectionDataAvailable', halo\event\READ));
            } catch(halo\event\BindException $e) {
                $this->_unregisterSessionBySocket($connection);
            }
            
            return;
        }
        
        $session->writeBuffer = substr($session->writeBuffer, $written);
    }
    
    
    
// Server events
    protected function _onSessionStart(ISession $session) {}
    protected function _onSessionEnd(ISession $session) {}
    
    protected function _canAccept(halo\socket\IServerSocket $socket) { return true; }
    protected function _handleReadBuffer(ISession $session, $data) {}
    protected function _handleWriteBuffer(ISession $session) {}
    
    protected function _onPeerShutdownWriting(ISession $session) {}
}