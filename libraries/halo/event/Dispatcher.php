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
    protected $_cycleHandler;
    protected $_handlers = array();
    protected $_signalHandlers = array();
    protected $_timerHandlers = array();
    
    public static function factory() {
        if(extension_loaded('libevent')) {
            return new halo\event\libevent\Dispatcher();
        }
        
        return new halo\event\select\Dispatcher();
    }


    public function isRunning() {
        return $this->_isRunning;
    }


// Cycle handler
    public function setCycleHandler(Callable $callback=null) {
        $this->_cycleHandler = $callback;
        return $this;
    }

    public function getCycleHandler() {
        return $this->_cycleHandler;
    }

    

// Socket
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

    public function removeSocket(halo\socket\ISocket $socket) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof ISocketHandler 
            && $handler->getSocket() === $socket) {
                return $this->removeHandler($handler);
            }
        }
        
        return $this;
    }
    

// Stream
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
    
    public function removeStream(core\io\stream\IStream $stream) {
        foreach($this->_handlers as $id => $handler) {
            if($handler instanceof IStreamHandler 
            && $handler->getStream() === $stream) {
                return $this->removeHandler($handler);
            }
        }
        
        return $this;
    }
    
    
    
    
// Handlers
    public function getHandlers() {
        return $this->_handlers;
    }
    
    public function countHandlers() {
        return count($this->_handlers) + count($this->_signalHandlers) + count($this->_timerHandlers);
    }

    public function removeHandler(IHandler $handler) {
        $id = $handler->getId();
        
        if(isset($this->_handlers[$id])) {
            $handler->clearBindings();
            unset($this->_handlers[$id]);
        }
        
        return $this;
    }
    
    
    public function removeAllHandlers() {
        foreach($this->_handlers as $handler) {
            $this->removeHandler($handler);
        }

        $this->removeSignalHandler(array_keys($this->_signalHandlers));
        
        return $this;
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




// Signals
    public function setSignalHandler($signals, Callable $handler) {
        if(!is_array($signals)) {
            $signals = (array)$signals;
        }

        foreach($signals as $signal) {
            $signal = halo\process\Signal::factory($signal);
            $this->_signalHandlers[$signal->getName()] = $handler;
            $this->_registerSignalHandler($signal, $handler);
        }

        return $this;
    }

    public function hasSignalHandler($signal) {
        try {
            $signal = halo\process\Signal::factory($signal);
            return isset($this->_signalHandlers[$signal->getName()]);
        } catch(\Exception $e) {
            return false;
        }
    }

    public function getSignalHandler($signal) {
        try {
            $signal = halo\process\Signal::factory($signal);
            
            if(isset($this->_signalHandlers[$signal->getName()])) {
                return $this->_signalHandlers[$signal->getName()];
            }
        } catch(\Exception $e) {
            return null;
        }
    }

    public function removeSignalHandler($signals) {
        if(!is_array($signal)) {
            $signals = (array)$signals;
        }

        foreach($signals as $signal) {
            $signal = halo\process\Signal::factory($signal);

            if(isset($this->_signalHandlers[$signal->getName()])) {
                unset($this->_signalHandlers[$signal->getName()]);
                $this->_unregisterSignalHandler($signal);
            }
        }

        return $this;
    }

    abstract protected function _registerSignalHandler(halo\process\ISignal $signal, Callable $handler);
    abstract protected function _unregisterSignalHandler(halo\process\ISignal $signal);


// Timers
    public function setTimer($id, $duration, Callable $callback) {
        if(isset($this->_timerHandlers[$id])) {
            $this->removeTimer($id);
        }

        $this->_timerHandlers[$id] = new Timer($id, $duration, $callback, true);
        $this->_registerTimer($this->_timerHandlers[$id]);

        return $this;
    }

    public function setTimeout($id, $duration, Callable $callback) {
        if(isset($this->_timerHandlers[$id])) {
            $this->removeTimer($id);
        }

        $this->_timerHandlers[$id] = new Timer($id, $duration, $callback);
        $this->_registerTimer($this->_timerHandlers[$id]);

        return $this;
    }

    public function hasTimer($id) {
        if($id instanceof Timer) {
            $id = $id->id;
        }

        return isset($this->_timerHandlers[$id]);
    }

    public function getTimer($id) {
        if($id instanceof Timer) {
            $id = $id->id;
        }

        if(isset($this->_timerHandlers[$id])) {
            return $this->_timerHandlers[$id];
        }
    }

    public function getTimerDuration($id) {
        if($timer = $this->getTimer($id)) {
            return $timer->duration;
        }
    }

    public function removeTimer($id) {
        if($id instanceof Timer) {
            $id = $id->id;
        }

        if(isset($this->_timerHandlers[$id])) {
            $this->_unregisterTimer($this->_timerHandlers[$id]);
            unset($this->_timerHandlers[$id]);
        }        

        return $this;
    }

    abstract protected function _registerTimer(Timer $timer);
    abstract protected function _unregisterTimer(Timer $timer);
} 