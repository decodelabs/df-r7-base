<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;

abstract class Dispatcher implements IDispatcher {
    
    protected $_isRunning = false;
    protected $_handlers = array();
    
    public static function factory() {
        if(extension_loaded('libevent')) {
            return new halo\event\libevent\Dispatcher();
        }
        
        return new halo\event\select\Dispatcher();
    }
    
    
    public function getSocketHandler(halo\socket\ISocket $socket) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof ISocketHandler 
            && $handler->getSocket() === $socket) {
                return $handler;
            }
        }
        
        throw new RuntimeException(
            'Socket handler '.$id.' has not been registered'
        );
    }
    
    public function getStreamHandler(core\io\stream\IStream $stream) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof IStreamHandler 
            && $handler->getStream() === $stream) {
                return $handler;
            }
        }
        
        throw new RuntimeException(
            'Stream handler '.$id.' has not been registered'
        );
    }
    
    public function getSignalHandler(halo\process\ISignal $signal) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof ISignalHandler 
            && $handler->getSignal() === $signal) {
                return $handler;
            }
        }
        
        throw new RuntimeException(
            'Signal handler '.$id.' has not been registered'
        );
    }
    
    public function getTimerHandler(core\time\IDuration $time) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof ITimerHandler 
            && $handler->getTimer() === $time) {
                return $handler;
            }
        }
        
        throw new RuntimeException(
            'Timer handler '.$id.' has not been registered'
        );
    }
    
    
    protected function _registerHandler(IHandler $handler) {
        $id = $handler->getId();
        
        if(isset($this->_handlers[$id])) {
            throw new RuntimeException(
                'Event '.$id.' has already been registered'
            );
        }
        
        $this->_handlers[$id] = $handler;
        return $handler;
    }
    
    
    public function isRunning() {
        return $this->_isRunning;
    }
    
    
    public function remove(IHandler $handler) {
        $id = $handler->getId();
        
        if(isset($this->_handlers[$id])) {
            $handler->clearBindings();
            unset($this->_handlers[$id]);
        }
        
        return $this;
    }
    
    public function removeSocket(halo\socket\ISocket $socket) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof ISocketHandler 
            && $handler->getSocket() === $socket) {
                return $this->remove($handler);
            }
        }
        
        return $this;
    }
    
    public function removeStream(core\io\stream\IStream $stream) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof IStreamHandler 
            && $handler->getStream() === $stream) {
                return $this->remove($handler);
            }
        }
        
        return $this;
    }
    
    public function removeSignal(halo\process\ISignal $signal) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof ISignalHandler 
            && $handler->getSignal() === $signal) {
                return $this->remove($handler);
            }
        }
        
        return $this;
    }
    
    public function removeTimer(core\time\IDuration $time) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof ITimerHandler 
            && $handler->getTimer() === $time) {
                return $this->remove($handler);
            }
        }
        
        return $this;
    }
    
    
    public function removeAll() {
        foreach($this->_handlers as $handler) {
            $this->remove($handler);
        }
        
        return $this;
    }
    
    public function getHandlers() {
        return $this->_handlers;
    }
    
    public function countHandlers() {
        return count($this->_handlers);
    }
} 