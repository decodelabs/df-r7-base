<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

abstract class HandlerBase extends halo\event\HandlerBase implements IHandler {
    
    protected function _registerBinding(halo\event\IBinding $binding) {
        $base = $this->_dispatcher->getEventBase();
        $event = event_new();
        
        if(!event_set(
            $event,
            $this->_getEventTarget(),
            Dispatcher::getEventTypeFlags($binding->getType(), $binding->isPersistent()),
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
    
    public function _handleEvent($socket, $flags, $binding) {
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
    
    protected function _getEventTimeout() {
        return -1;
    }
}