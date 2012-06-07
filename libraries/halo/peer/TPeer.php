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
    protected $_isStarted = false;
    protected $_sessions = array();
    protected $_sessionCount = 0;
    
    
// Dispatcher
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
    
    
// Protocol
    public function getProtocolDisposition() {
        return static::PROTOCOL_DISPOSITION;
    }
    
    
// Registration
    protected function _registerSession(halo\socket\ISocket $socket) {
        $socket->setSessionId(++$this->_sessionCount);
        
        $session = $this->_createSession($socket);
        $this->_sessions[$session->getId()] = $session;
        $this->_onSessionStart($session);
        
        return $session;
    }
    
    protected function _createSession(halo\socket\ISocket $socket) {
        return new Session($socket);
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
                
                try {
                    $handler->unfreeze($handler->getBinding($this, 'connectionWaiting', halo\event\WRITE));
                } catch(halo\event\BindException $e) {
                    core\dump($e);
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
                        core\dump($e);
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
