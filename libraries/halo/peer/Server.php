<?php

namespace df\halo\peer;

use df\core;
use df\halo;

abstract class Server implements IServer {
    
    use TPeer;
    
    const SERVER_FIRST = 1;
    const PEER_FIRST = 2;
    const SERVER_STREAM = 3;
    const PEER_STREAM = 4;
    const DUPLEX_STREAM = 5;
    
    const PROTOCOL_DISPOSITION = self::PEER_FIRST;
    
    protected $_masterSockets = array();
    protected $_requests = 0;
    
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
    
    
    
// Events
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
                $eventHandler->bindWrite($this, 'connectionWaiting', true, [$session]);
                $eventHandler->freeze($eventHandler->bind($this, 'dataAvailable', true, [$session]));
                break;
                
            case self::PEER_FIRST:
                $eventHandler->bind($this, 'dataAvailable', true, [$session]);
                $eventHandler->freeze($eventHandler->bindWrite($this, 'connectionWaiting', true, [$session]));
                break;
                
            case self::SERVER_STREAM:
                core\stub('Unable to handle server streams');
                $eventHandler->bindWrite($this, 'streamConnectionWaiting', true);
                break;
                
            case self::PEER_STREAM:
                core\stub('Unable to handle peer streams');
                $eventHandler->bind($this, 'streamDataAvailable', true);
                break;
                
            case self::DUPLEX_STREAM:
                core\stub('Unable to handle duplex streams');
                break;
            
            default:
                core\stub('Unknown protocol disposition');
        }
            
        //echo 'Accept complete - '.$connection->getSessionId()."\n";
    }
    
    
    
// Event stubs
    protected function _canAccept(halo\socket\IServerSocket $socket) { return true; }
}