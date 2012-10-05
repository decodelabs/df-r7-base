<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

abstract class Handler implements halo\event\IHandler {
    
    protected function _registerBinding(halo\event\IBinding $binding) {
        $base = $this->_dispatcher->getEventBase();
        $event = event_new();
        
        if(!event_set(
            $event,
            $this->_getEventTarget(),
            $this->_getEventTypeFlags($binding),
            array($this, '_handleEvent'),
            $binding
        )) {
            event_free($event);
            
            throw new halo\event\BindException(
                'Could not set event: '.$this->getId()
            );
        }
        
        if(!event_base_set($event, $base)) {
            event_free($event);
            
            throw new halo\event\BindException(
                'Could not set event base: '.$this->getId()
            );
        }
        
        if(!event_add($event, $this->_getEventTimeout())) {
            event_free($event);
            
            throw new halo\event\BindException(
                'Could not add event: '. $this->getId()
            );
        }
        
        $binding->setEventResource($event);
            
        //echo 'Event attached: '.$this->getId().', binding: '.$binding->getId()."\n";
    }
    
    protected function _unregisterBinding(halo\event\IBinding $binding) {
        if($event = $binding->getEventResource()) {
            event_del($event);
            event_free($event);
        }
    }
    
    public function _handleEvent($target, $flags, $binding) {
        if(!$binding->isPersistent()) {
            $binding->isAttached(false);
        }
        
        $binding->trigger($this);
    }
    
    
    public function freeze(halo\event\IBinding $binding) {
        $binding->isAttached(false);
        
        if($event = $binding->getEventResource()) {
            event_del($event);
        }
        
        return $this;
    }
    
    public function unfreeze(halo\event\IBinding $binding) {
        if($binding->isAttached()) {
            return $this;
        }
        
        if(!$event = $binding->getEventResource()) {
            throw new halo\event\BindException(
                'Binding is destroyed, cannot unfreeze'
            );
        }
        
        if(!event_add($event, $this->_getEventTimeout())) {
            event_free($event);
            $binding->setEventResource(null);
            
            throw new halo\event\BindException(
                'Could not add event: '. $this->getId()
            );
        }
        
        $binding->isAttached(true);
        return $this;
    }
    
    abstract protected function _getEventTarget();

    protected function _getEventTypeFlags(halo\event\IBinding $binding) {
        switch($binding->getType()) {
            case halo\event\IIoState::READ:
                $flags = EV_READ;
                break;
                
            case halo\event\IIoState::WRITE:
                $flags = EV_WRITE;
                break;
                
            case halo\event\IIoState::READ_WRITE:
                $flags = EV_READ | EV_WRITE;
                break;
                
            case halo\event\IIoState::TIMEOUT:
                $flags = EV_TIMEOUT;
                break;
                
            default:
                throw new halo\event\InvalidArgumentException(
                    'Unknown event type: '.$type
                );
        }
        
        if($binding->isPersistent()) {
            $flags |= EV_PERSIST;
        }
        
        return $flags;
    }
    
    protected function _getEventTimeout() {
        return -1;
    }
}



// Signal
class Handler_Signal extends Handler implements halo\event\ISignalHandler {
        
    use halo\event\TSignalHandler;

    protected function _getEventTarget() {
        return $this->_signal->getNumber();
    }

    protected function _getEventTypeFlags(halo\event\IBinding $binding) {
        $output = EV_SIGNAL;

        if($binding->isPersistent()) {
            $output |= EV_PERSIST;
        }

        return $output;
    }
}


// Socket
class Handler_Socket extends Handler implements halo\event\ISocketHandler {
    
    use halo\event\TSocketHandler;

    protected function _getEventTarget() {
        return $this->_socket->getSocketDescriptor();
    }
}


// Stream
class Handler_Stream extends Handler implements halo\event\IStreamHandler {
    
    use halo\event\TStreamHandler;

    protected function _getEventTarget() {
        return $this->_stream->getStreamDescriptor();
    }
}


// Timer
class Handler_Timer extends Handler implements halo\event\ITimerHandler {
    
    use halo\event\TTimerHandler;
    
    public function getBinding($listener, $bindingName, $type=halo\event\IIoState::TIMEOUT) {
        return parent::getBinding($listener, $bindingName, halo\event\IIoState::TIMEOUT);
    }
    
    protected function _getEventTarget() {
        if(defined('STDIN')) {
            return STDIN;
        }
        
        return fopen('php://stdin', 'r');
    }
    
    protected function _getEventTimeout() {
        return $this->_time->getMicroseconds();
    }
}