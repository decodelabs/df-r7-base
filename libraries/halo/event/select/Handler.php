<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\select;

use df;
use df\core;
use df\halo;

abstract class Handler implements halo\event\IHandler {
    
    protected function _registerBinding(halo\event\IBinding $binding) {
        $this->_dispatcher->regenerateMaps();
    }
    
    protected function _unregisterBinding(halo\event\IBinding $binding) {
        $this->_dispatcher->regenerateMaps();
    }
    
    public function freeze(halo\event\IBinding $binding) {
        $binding->isAttached(false);
        return $this;
    }
    
    public function unfreeze(halo\event\IBinding $binding) {
        $binding->isAttached(true);
        return $this;
    }
    
    protected function _getEventTimeout() {
        return -1;
    }

    abstract public function _exportToMap(&$map);
}



// Signal
class Handler_Signal extends Handler implements halo\event\ISignalHandler {
    
    use halo\event\TSignalHandler;

    protected function _registerBinding(halo\event\IBinding $binding) {
        if(extension_loaded('pcntl')) {
            pcntl_signal($this->_signal->getNumber(), function() use ($binding) { $binding->trigger($this); });
        }

        parent::_registerBinding($binding);
    }
    
    protected function _unregisterBinding(halo\event\IBinding $binding) {
        if(extension_loaded('pcntl')) {
            pcntl_signal($this->_signal->getNumber(), function() {});
        }

        parent::_unregisterBinding($binding);
    }

    public function _exportToMap(&$map) {
        $id = $this->getId();
        
        $map[Dispatcher::SIGNAL][$this->_signal->getName()][] = $this;
        $map[Dispatcher::COUNTER][Dispatcher::SIGNAL]++;
    }
}



// Socket
class Handler_Socket extends Handler implements halo\event\ISocketHandler {
    
    use halo\event\TSocketHandler;

    protected $_readCount = 0;
    protected $_writeCount = 0;
    
    protected function _registerBinding(halo\event\IBinding $binding) {
        switch($binding->getType()) {
            case halo\event\IIoState::READ:
                $this->_readCount++;
                break;
                
            case halo\event\IIoState::WRITE:
                $this->_writeCount++;
                break;
                
            case halo\event\IIoState::READ_WRITE:
                $this->_readCount++;
                $this->_writeCount++;
                break;
                
            case halo\event\IIoState::TIMEOUT:
                // TODO: handle timeouts
                break;
        }
        
        parent::_registerBinding($binding);
    }
    
    protected function _unregisterBinding(halo\event\IBinding $binding) {
        switch($binding->getType()) {
            case halo\event\IIoState::READ:
                $this->_readCount--;
                break;
                
            case halo\event\IIoState::WRITE:
                $this->_writeCount--;
                break;
                
            case halo\event\IIoState::READ_WRITE:
                $this->_readCount--;
                $this->_writeCount--;
                break;
                
            case halo\event\IIoState::TIMEOUT:
                // TODO: handle timeouts
                break;
        }
        
        parent::_unregisterBinding($binding);
    }
    
    public function _handleEvent($type) {
        foreach($this->_bindings as $binding) {
            if(!$binding->isAttached()) {
                continue;
            }
            
            if($binding->getType() == $type) {
                $binding->trigger($this);
            }
        }
    }
    
    public function _exportToMap(&$map) {
        $resource = $this->_socket->getSocketDescriptor();
        $id = (int)$resource;
        $key = $this->_socket->getImplementationName() == 'streams' ?
            Dispatcher::STREAM : Dispatcher::SOCKET;
            
        if($this->_readCount) {
            $map[$key][Dispatcher::RESOURCE][Dispatcher::READ][$id] = $resource;
        }
        
        if($this->_writeCount) {
            $map[$key][Dispatcher::RESOURCE][Dispatcher::WRITE][$id] = $resource;
        }
        
        $map[$key][Dispatcher::HANDLER][$id] = $this;
        $map[Dispatcher::COUNTER][$key]++;
    }
}



// Stream
class Handler_Stream extends Handler implements halo\event\IStreamHandler {
    
    use halo\event\TStreamHandler;
    
    protected $_readCount = 0;
    protected $_writeCount = 0;
    
    protected function _registerBinding(halo\event\IBinding $binding) {
        switch($binding->getType()) {
            case halo\event\IIoState::READ:
                $this->_readCount++;
                break;
                
            case halo\event\IIoState::WRITE:
                $this->_writeCount++;
                break;
                
            case halo\event\IIoState::READ_WRITE:
                $this->_readCount++;
                $this->_writeCount++;
                break;
                
            case halo\event\IIoState::TIMEOUT:
                // TODO: handle timeouts
                break;
        }
        
        parent::_registerBinding($binding);
    }
    
    protected function _unregisterBinding(halo\event\IBinding $binding) {
        switch($binding->getType()) {
            case halo\event\IIoState::READ:
                $this->_readCount--;
                break;
                
            case halo\event\IIoState::WRITE:
                $this->_writeCount--;
                break;
                
            case halo\event\IIoState::READ_WRITE:
                $this->_readCount--;
                $this->_writeCount--;
                break;
                
            case halo\event\IIoState::TIMEOUT:
                // TODO: handle timeouts
                break;
        }
        
        parent::_unregisterBinding($binding);
    }
    
    public function _exportToMap(&$map) {
        $resource = $this->_stream->getStreamDescriptor();
        $id = (int)$resource;
        
        if($this->_readCount) {
            $map[Dispatcher::STREAM][Dispatcher::RESOURCE][Dispatcher::READ][$id] = $resource;
        }
        
        if($this->_writeCount) {
            $map[Dispatcher::STREAM][Dispatcher::RESOURCE][Dispatcher::WRITE][$id] = $resource;
        }
        
        $map[Dispatcher::STREAM][Dispatcher::HANDLER][$id] = $this;
        $map[Dispatcher::COUNTER][$key]++;
    }
}


// Timer
class Handler_Timer extends Handler implements halo\event\ITimerHandler {
    
    use halo\event\TTimerHandler;
    
    public function getBinding($listener, $bindingName, $type=halo\event\IIoState::TIMEOUT) {
        return parent::getBinding($listener, $bindingName, halo\event\IIoState::TIMEOUT);
    }
    
    protected function _getEventTimeout() {
        return $this->_time->getMicroseconds();
    }

    public function _handleEvent() {
        foreach($this->_bindings as $binding) {
            if(!$binding->isAttached()) {
                continue;
            }
            
            $binding->trigger($this);
        }
    }
    
    public function _exportToMap(&$map) {
        $id = $this->getId();

        $map[Dispatcher::TIMER][Dispatcher::HANDLER][$id] = $this;
        $map[Dispatcher::TIMER][Dispatcher::RESOURCE][$id] = $this->_time->getSeconds();
        $map[Dispatcher::COUNTER][Dispatcher::TIMER]++;
    }
}