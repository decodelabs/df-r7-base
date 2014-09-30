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
    
    protected function _getEventTimeout() {
        return -1;
    }

    abstract public function _exportToMap(&$map);
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
        $map[Dispatcher::COUNTER][Dispatcher::STREAM]++;
    }
}