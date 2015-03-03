<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;
use df\link;
use df\mesh;

class Event extends Base implements core\IDumpable {
    
    protected $_base;
    protected $_cycleHandlerEvent;

    public function __construct() {
        $this->_base = new \EventBase();
    }

    public function getEventBase() {
        return $this->_base;
    }

    public function listen() {
        $this->_isListening = true;
        $this->_base->loop();
        $this->_isListening = false;

        return $this;
    }

    public function stop() {
        if($this->_isListening) {
            $this->_base->exit();
            $this->_isListening = false;
        }

        return $this;
    }



// Bindings
    public function freezeBinding(IBinding $binding) {
        if($binding->isFrozen) {
            return $this;
        }

        $func = '_unregister'.$binding->getType().'Binding';
        $this->{$func}($binding);
        $binding->isFrozen = true;

        return $this;
    }

    public function unfreezeBinding(IBinding $binding) {
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
            $this->_cycleHandlerEvent->del();
            $this->_cycleHandlerEvent->free();
            $this->_cycleHandlerEvent = null;
        }

        if($this->_cycleHandler) {
            $this->_cycleHandlerEvent = $this->_registerEvent(
                null,
                \Event::TIMEOUT | \Event::PERSIST,
                1000,
                [$this, '_handleCycle']
            );
        }
    }

    public function _handleCycle() {
        $this->_registerCycleHandler();

        if($this->_cycleHandler) {
            if(false === $this->_cycleHandler->invokeArgs([$this])) {
                $this->stop();
                return;
            }
        }
    }


// Sockets
    protected function _registerSocketBinding(ISocketBinding $binding) {
        $binding->eventResource = $this->_registerEvent(
            $binding->socket->getSocketDescriptor(),
            $this->_getIoEventFlags($binding),
            $this->_getTimeoutDuration($binding),
            [$this, '_handleSocketBinding'],
            $binding
        );
    }

    protected function _unregisterSocketBinding(ISocketBinding $binding) {
        if($binding->eventResource) {
            $binding->eventResource->del();
            $binding->eventResource->free();
            $binding->eventResource = null;
        }
    }

    public function _handleSocketBinding($target, $flags, ISocketBinding $binding) {
        if($flags & \Event::TIMEOUT) {
            $binding->triggerTimeout($target);
        } else {
            $binding->trigger($target);
        }

        if(!$binding->isPersistent) {
            $this->_unregisterSocketBinding($binding);
        }
    }



// Streams
    protected function _registerStreamBinding(IStreamBinding $binding) {
        $binding->eventResource = $this->_registerEvent(
            $binding->stream->getStreamDescriptor(),
            $this->_getIoEventFlags($binding),
            $this->_getTimeoutDuration($binding),
            [$this, '_handleStreamBinding'],
            $binding
        );
    }

    protected function _unregisterStreamBinding(IStreamBinding $binding) {
        if($binding->eventResource) {
            $binding->eventResource->del();
            $binding->eventResource->free();
            $binding->eventResource = null;
        }
    }

    public function _handleStreamBinding($target, $flags, IStreamBinding $binding) {
        if($flags & \Event::TIMEOUT) {
            $binding->triggerTimeout($target);
        } else {
            $binding->trigger($target);
        }

        if(!$binding->isPersistent) {
            $this->_unregisterStreamBinding($binding);
        }
    }


// Signals
    protected function _registerSignalBinding(ISignalBinding $binding) {
        $flags = \Event::SIGNAL;

        if($binding->isPersistent) {
            $flags |= \Event::PERSIST;
        }

        foreach($binding->signals as $number => $signal) {
            $binding->eventResource[$number] = $this->_registerEvent(
                $number,
                $flags,
                null,
                [$this, '_handleSignalBinding'],
                $binding
            );
        }
    }

    protected function _unregisterSignalBinding(ISignalBinding $binding) {
        foreach($binding->eventResource as $number => $resource) {
            if(!$resource) {
                continue;
            }

            $resource->del();
            $resource->free();
            $binding->eventResource[$number] = null;
        }
    }

    public function _handleSignalBinding($number, ISignalBinding $binding) {
        $binding->trigger($number);

        if($binding->isPersistent) {
            foreach($binding->eventResource as $event) {
                $event->add();
            }
        } else {
            $this->_unregisterSignalBinding($binding);
        }
    }




// Timers
    protected function _registerTimerBinding(ITimerBinding $binding) {
        $flags = \Event::TIMEOUT;

        if($binding->isPersistent) {
            $flags |= \Event::PERSIST;
        }

        $binding->eventResource = $this->_registerEvent(
            null,
            $flags,
            $binding->duration->getMilliseconds(),
            [$this, '_handleTimerBinding'],
            $binding
        );
    }

    protected function _unregisterTimerBinding(ITimerBinding $binding) {
        if($binding->eventResource) {
            $binding->eventResource->del();
            $binding->eventResource->free();
            $binding->eventResource = null;
        }
    }

    public function _handleTimerBinding(ITimerBinding $binding) {
        $binding->trigger(null);

        if($binding->isPersistent) {
            $binding->eventResource->add($binding->duration->getMilliseconds());
        } else {
            $this->_unregisterTimerBinding($binding);
        }
    }


// Helpers
    public function _registerEvent($target, $flags, $timeout, Callable $callback, $arg=null) {
        if($timeout <= 0) {
            $timeout = null;
        } else {
            $timeout = $timeout / 1000;
        }

        if($flags & \Event::SIGNAL) {
            $event = \Event::signal($this->_base, $target, $callback, $arg);
        } else if($target === null) {
            $event = \Event::timer($this->_base, $callback, $arg);
        } else {
            $event = new \Event($this->_base, $target, $flags, $callback, $arg);
        }

        if($timeout !== null) {
            $res = $event->add((int)$timeout);
        } else {
            $res = $event->add();
        }

        if(!$res) {
            $event->free();

            throw new BindException(
                'Could not add event'
            );
        }

        return $event;
    }

    protected function _getIoEventFlags(IIoBinding $binding) {
        switch($binding->ioMode) {
            case IIoState::READ:
                $flags = \Event::READ;
                break;
                
            case IIoState::WRITE:
                $flags = \Event::WRITE;
                break;
                
            default:
                throw new InvalidArgumentException(
                    'Unknown event type: '.$type
                );
        }
        
        if($binding->isPersistent) {
            $flags |= \Event::PERSIST;
        }
        
        return $flags;
    }

    protected function _getTimeoutDuration(IBinding $binding) {
        if($binding instanceof IIoBinding) {
            return $binding->timeoutDuration ? 
                $binding->timeoutDuration->getMilliseconds() : 
                null;
        } else {
            return null;
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