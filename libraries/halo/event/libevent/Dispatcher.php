<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

class Dispatcher extends halo\event\Dispatcher {
    
    protected $_base;
    protected $_cycleHandler;
    protected $_cycleHandlerEvent;
    
    public function __construct() {
        $this->_base = event_base_new();
    }
    
    public function getEventBase() {
        return $this->_base;
    }
    
    public function start() {
        echo "Starting event loop\n\n";
        
        $this->_isRunning = true;
        event_base_loop($this->_base);
        $this->_isRunning = false;
        
        echo "\nEnding event loop\n";
        
        return $this;
    }
    
    public function stop() {
        if($this->_isRunning) {
            event_base_loopexit($this->_base);
            $this->_isRunning = false;
        }
        
        return $this;
    }
    
    
    public function newSocketHandler(halo\socket\ISocket $socket) {
        return $this->_registerHandler(new Handler_Socket($this, $socket));
    }
    
    public function newStreamHandler(core\io\stream\IStream $stream) {
        return $this->_registerHandler(new Handler_Stream($this, $stream));
    }
    
    public function newSignalHandler(halo\process\ISignal $signal) {
        return $this->_registerHandler(new Handler_Signal($this, $signal));
    }
    
    public function newTimerHandler(core\time\IDuration $time) {
        return $this->_registerHandler(new Handler_Timer($this, $time));
    }

    public function setCycleHandler(Callable $callback=null) {
        if($this->_cycleHandlerEvent) {
            event_del($this->_cycleHandlerEvent);
            event_free($this->_cycleHandlerEvent);
        }

        $this->_cycleHandler = $callback;

        if($callback) {
            $this->_cycleHandlerEvent = event_new();

            if(!event_set(
                $this->_cycleHandlerEvent,
                STDIN,
                EV_TIMEOUT | EV_PERSIST,
                function() use ($callback) {
                    if(false === call_user_func_array($callback, [$this])) {
                        $this->stop();
                    }
                }
            )) {
                event_free($this->_cycleHandlerEvent);

                throw new halo\event\BindException(
                    'Could not set cycle event'
                );
            }

            if(!event_base_set($this->_cycleHandlerEvent, $this->_base)) {
                event_free($this->_cycleHandlerEvent);

                throw new halo\event\BindException(
                    'Could not set cycle event base'
                );
            }

            if(!event_add($this->_cycleHandlerEvent, 1000000)) {
                event_free($this->_cycleHandlerEvent);

                throw new halo\event\BindException(
                    'Could not add cycle event'
                );
            }
        }

        return $this;
    }

    public function getCycleHandler() {
        return $this->_cycleHandler;
    }
}