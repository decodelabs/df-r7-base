<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;
use df\link;
use df\mesh;

class Dispatcher extends halo\event\Dispatcher implements core\IDumpable {
    
    protected $_base;
    protected $_cycleHandlerEvent;
    
    public function __construct() {
        $this->_base = event_base_new();
    }
    
    public function getEventBase() {
        return $this->_base;
    }
    
    public function listen() {
        $this->_isListening = true;
        event_base_loop($this->_base);
        $this->_isListening = false;
        
        return $this;
    }
    
    public function stop() {
        if($this->_isListening) {
            event_base_loopexit($this->_base);
            $this->_isListening = false;
        }
        
        return $this;
    }



    public function freezeBinding(halo\event\IBinding $binding) {
        if($binding->isFrozen) {
            return $this;
        }

        $func = '_unregister'.$binding->getType().'Binding';
        $this->{$func}($binding);
        $binding->isFrozen = true;

        return $this;
    }

    public function unfreezeBinding(halo\event\IBinding $binding) {
        if(!$binding->isFrozen) {
            return $this;
        }

        $func = '_register'.$binding->getType().'Binding';
        $this->{$func}($binding);
        $binding->isFrozen = false;

        return $this;
    }


// Cycle handler
    public function setCycleHandler($callback=null) {
        parent::setCycleHandler($callback);
        $this->_registerCycleHandler();

        return $this;
    }

    protected function _registerCycleHandler() {
        if($this->_cycleHandlerEvent) {
            event_del($this->_cycleHandlerEvent);
            event_free($this->_cycleHandlerEvent);
        }

        if($this->_cycleHandler) {
            $this->_cycleHandlerEvent = $this->_registerEvent(
                null,
                EV_TIMEOUT | EV_PERSIST,
                1000,
                [$this, '_handleCycle']
            );
        }
    }

    protected function _handleCycle() {
        $this->_registerCycleHandler();

        if($this->_cycleHandler) {
            if(false === $this->_cycleHandler->invokeArgs([$this])) {
                $this->stop();
                return;
            }
        }
    }



// Sockets
    protected function _registerSocketBinding(halo\event\ISocketBinding $binding) {
        $binding->eventResource = $this->_registerEvent(
            $binding->socket->getSocketDescriptor(),
            $this->_getIoEventFlags($binding),
            $this->_getTimeoutDuration($binding),
            [$this, '_handleSocketBinding'],
            $binding
        );
    }

    protected function _unregisterSocketBinding(halo\event\ISocketBinding $binding) {
        if($binding->eventResource) {
            event_del($binding->eventResource);
            event_free($binding->eventResource);
            $binding->eventResource = null;
        }
    }

    protected function _handleSocketBinding($target, $flags, halo\event\ISocketBinding $binding) {
        if($flags & EV_TIMEOUT) {
            $binding->triggerTimeout($target);
        } else {
            $binding->trigger($target);
        }
    }



// Streams
    protected function _registerStreamBinding(halo\event\IStreamBinding $binding) {
        $binding->eventResource = $this->_registerEvent(
            $binding->stream->getStreamDescriptor(),
            $this->_getIoEventFlags($binding),
            $this->_getTimeoutDuration($binding),
            [$this, '_handleStreamBinding'],
            $binding
        );
    }

    protected function _unregisterStreamBinding(halo\event\IStreamBinding $binding) {
        if($binding->eventResource) {
            event_del($binding->eventResource);
            event_free($binding->eventResource);
            $binding->eventResource = null;
        }
    }

    protected function _handleStreamBinding($target, $flags, halo\event\IStreamBinding $binding) {
        if($flags & EV_TIMEOUT) {
            $binding->triggerTimeout($target);
        } else {
            $binding->trigger($target);
        }
    }


// Signals
    protected function _registerSignalBinding(halo\event\ISignalBinding $binding) {
        $flags = EV_SIGNAL;

        if($binding->isPersistent) {
            $flags |= EV_PERSIST;
        }

        foreach($binding->signals as $number => $signal) {
            $binding->eventResource[$number] = $this->_registerEvent(
                $number,
                $flags,
                -1,
                [$this, '_handleSignalBinding'],
                [$number, $binding]
            );
        }
    }

    protected function _unregisterSignalBinding(halo\event\ISignalBinding $binding) {
        foreach($binding->eventResource as $number => $resource) {
            if(!$resource) {
                continue;
            }

            event_del($resource);
            event_free($resource);
            $binding->eventResource[$number] = null;
        }
    }

    /*
     * We have to pass the args as array as the signal number is not being propagated. Argh :(
     */
    protected function _handleSignalBinding($number, $flags, array $args) {
        $number = array_shift($args);
        $binding = array_shift($args);

        $binding->trigger($number);
    }




// Timers
    protected function _registerTimerBinding(halo\event\ITimerBinding $binding) {
        $flags = EV_TIMEOUT;

        if($binding->isPersistent) {
            $flags |= EV_PERSIST;
        }

        $binding->eventResource = $this->_registerEvent(
            null,
            $flags,
            $binding->duration->getMilliseconds(),
            [$this, '_handleTimerBinding'],
            $binding
        );
    }

    protected function _unregisterTimerBinding(halo\event\ITimerBinding $binding) {
        if($binding->eventResource) {
            event_del($binding->eventResource);
            event_free($binding->eventResource);
            $binding->eventResource = null;
        }
    }

    protected function _handleTimerBinding($target, $flags, halo\event\ITimerBinding $binding) {
        $binding->trigger(null);

        if($binding->isPersistent) {
            $this->_registerTimerBinding($binding);
        }
    }



// Helpers
    public function _registerEvent($target, $flags, $timeout, Callable $callback, $arg=null) {
        if(!is_int($timeout) || $timeout < 0) {
            $timeout = -1;
        }

        if($timeout != -1) {
            $timeout *= 1000;
        }

        $event = event_new();

        if($target === null) {
            $ret = event_timer_set($event, $callback, $arg);
        } else {
            $ret = event_set($event, $target, $flags, $callback, $arg);
        }

        if(!$ret) {
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

    protected function _getIoEventFlags(halo\event\IIoBinding $binding) {
        switch($binding->ioMode) {
            case halo\event\IIoState::READ:
                $flags = EV_READ;
                break;
                
            case halo\event\IIoState::WRITE:
                $flags = EV_WRITE;
                break;
                
            default:
                throw new halo\event\InvalidArgumentException(
                    'Unknown event type: '.$type
                );
        }
        
        if($binding->isPersistent) {
            $flags |= EV_PERSIST;
        }
        
        return $flags;
    }

    protected function _getTimeoutDuration(halo\event\IBinding $binding) {
        if($binding instanceof halo\event\IIoBinding) {
            return $binding->timeoutDuration ? 
                $binding->timeoutDuration->getMilliseconds() : 
                -1;
        } else {
            return -1;
        }
    }


// Dump
    public function getDumpProperties() {
        return [
            'base' => $this->_base,
            'cycleHandler' => $this->_cycleHandler,
            'sockets' => $this->_socketBindings,
            'streams' => $this->_streamBindings,
            'signals' => $this->_signalBindings,
            'timers' => $this->_timerBindings
        ];
    }
}