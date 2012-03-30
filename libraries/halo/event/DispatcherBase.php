<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;

abstract class DispatcherBase implements IDispatcher {
    
    protected $_isRunning = false;
    protected $_handlers = array();
    
    public static function getSocketHandlerId(halo\socket\ISocket $socket) {
        return 'socket:'.$socket->getId();
    }
    
    public static function getStreamHandlerId(core\io\stream\IStream $stream) {
        return 'stream:'.$stream->getId();
    }
    
    public static function getSignalHandlerId($signal) {
        return 'signal:'.$signal;
    }
    
    public static function getTimerHandlerId(core\time\IDuration $time) {
        return 'time:'.$time->getSeconds();
    }
    
    
    public static function factory() {
        if(extension_loaded('libevent')) {
            return new halo\event\libevent\Dispatcher();
        }
        
        return new halo\event\select\Dispatcher();
    }
    
    
    public function getSocketHandler(halo\socket\ISocket $socket) {
        $id = self::getSocketHandlerId($socket);
        
        if(isset($this->_handlers[$id])) {
            return $this->_handlers[$id];
        }
        
        throw new RuntimeException(
            'Socket handler '.$id.' has not been registered'
        );
    }
    
    public function getStreamHandler(core\io\stream\IStream $stream) {
        $id = self::getStreamHandlerId($stream);
        
        if(isset($this->_handlers[$id])) {
            return $this->_handlers[$id];
        }
        
        throw new RuntimeException(
            'Stream handler '.$id.' has not been registered'
        );
    }
    
    public function getSignalHandler($signal) {
        $id = self::getSignalHandlerId($signal);
        
        if(isset($this->_handlers[$id])) {
            return $this->_handlers[$id];
        }
        
        throw new RuntimeException(
            'Signal handler '.$id.' has not been registered'
        );
    }
    
    public function getTimerHandler(core\time\IDuration $time) {
        $id = self::getTimerHandlerId($time);
        
        if(isset($this->_handlers[$id])) {
            return $this->_handlers[$id];
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
        $id = self::getSocketHandlerId($socket);
        
        if(isset($this->_handlers[$id])) {
            $this->remove($this->_handlers[$id]);
        }
        
        return $this;
    }
    
    public function removeStream(core\io\stream\IStream $stream) {
        $id = self::getStreamHandlerId($stream);
        
        if(isset($this->_handlers[$id])) {
            $this->remove($this->_handlers[$id]);
        }
        
        return $this;
    }
    
    public function removeSignal($signal) {
        $id = self::getSignalHandlerId($signal);
        
        if(isset($this->_handlers[$id])) {
            $this->remove($this->_handlers[$id]);
        }
        
        return $this;
    }
    
    public function removeTimer(core\time\IDuration $time) {
        $id = self::getTimerHandlerId($time);
        
        if(isset($this->_handlers[$id])) {
            $this->remove($this->_handlers[$id]);
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