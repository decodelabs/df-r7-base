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
    protected $_cycleHandlerEvent;
    protected $_signalEvents = array();
    protected $_timerEvents = array();
    
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
    
    public function setCycleHandler(Callable $callback=null) {
        if($this->_cycleHandlerEvent) {
            event_del($this->_cycleHandlerEvent);
            event_free($this->_cycleHandlerEvent);
        }

        $this->_cycleHandler = $callback;

        if($callback) {
            $this->_cycleHandlerEvent = $this->_registerEvent(
                STDIN,
                EV_TIMEOUT | EV_PERSIST,
                1000000,
                function() use ($callback) {
                    if(false === call_user_func_array($callback, [$this])) {
                        $this->stop();
                    }
                }
            );
        }

        return $this;
    }


// Signals
    protected function _registerSignalHandler(halo\process\ISignal $signal, Callable $callback) {
        $this->_unregisterSignalHandler($signal);

        $this->_signalEvents[$signal->getName()] = $this->_registerEvent(
            $signal->getNumber(),
            EV_SIGNAL | EV_PERSIST,
            -1,
            $callback,
            $signal
        );
    }

    protected function _unregisterSignalHandler(halo\process\ISignal $signal) {
        $name = $signal->getName();

        if(isset($this->_signalEvents[$name])) {
            event_del($this->_signalEvents[$name]);
            event_free($this->_signalEvents[$name]);

            unset($this->_signalEvents[$name]);
        }
    }


// Timers
    protected function _registerTimer(halo\event\Timer $timer) {
        $this->_unregisterTimer($timer);

        $flags = EV_TIMEOUT;

        if($timer->isPersistent) {
            $flags |= EV_PERSIST;
        }

        $this->_timerEvents[$timer->id] = $this->_registerEvent(
            STDIN,
            $flags,
            $timer->duration->getMicroseconds(),
            [$this, '_handleTimerEvent'],
            $timer
        );
    }

    protected function _unregisterTimer(halo\event\Timer $timer) {
        if(isset($this->_timerEvents[$timer->id])) {
            event_del($this->_timerEvents[$timer->id]);
            event_free($this->_timerEvents[$timer->id]);

            unset($this->_timerEvents[$timer->id]);
        }
    }

    public function _handleTimerEvent($target, $flags, halo\event\Timer $timer) {
        call_user_func_array($timer->callback, [$timer->id]);

        if(!$timer->isPersistent) {
            $this->removeTimer($timer);
        }
    }


// Helpers
    public function _registerEvent($target, $flags, $timeout, Callable $callback, $arg=null) {
        if(!is_int($timeout) || $timeout < 0) {
            $timeout = -1;
        }

        $event = event_new();

        if(!event_set($event, $target, $flags, $callback, $arg)) {
            event_free($event);

            throw new halo\event\BindException(
                'Could not set event'
            );
        }

        if(!event_base_set($event, $this->_base)) {
            event_free($event);

            throw new halo\event\BindException(
                'Could not set event base'
            );
        }

        if(!event_add($event, $timeout)) {
            event_free($event);

            throw new halo\event\BindException(
                'Could not add event'
            );
        }

        return $event;
    }
}