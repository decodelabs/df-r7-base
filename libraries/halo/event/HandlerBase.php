<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;

abstract class HandlerBase implements IHandler {
    
    protected $_bindings = array();
    protected $_dispatcher;
    
    public function __construct(IDispatcher $dispatcher) {
        $this->_dispatcher = $dispatcher;
    }
    
    public function getScheme() {
        if($this instanceof ISocketHandler) {
            return 'socket';
        } else if($this instanceof IStreamHandler) {
            return 'stream';
        } else if($this instanceof ISignalHandler) {
            return 'signal';
        } else if($this instanceof ITimerHandler) {
            return 'timer';
        }
        
        throw new InvalidArgumentException(
            'Unknown event scheme - '.get_class($this)
        );
    }
    
    public function getDispatcher() {
        return $this->_dispatcher;
    }
    
    
// Bindings
    public function bind(IListener $listener, $bindingName, $persistent=false, array $args=null) {
        return $this->_bind(new Binding($this, $listener, halo\event\READ, $bindingName, $persistent, $args));
    }
    
    public function rebind(IBinding $binding) {
        return $this->_bind($binding);
    }
    
    protected function _bind(IBinding $binding) {
        if($binding->isAttached()) {
            throw new BindException(
                'This binding appears to already be assigned'
            );
        }
        
        $id = $binding->getId();
        
        if(isset($this->_bindings[$id])) {
            throw new BindException(
                'Binding '.$id.' has already been created'
            );
        }
        
        $this->_registerBinding($binding);
        $binding->isAttached(true);
        $this->_bindings[$id] = $binding;
        
        return $binding;
    }
    
    abstract protected function _registerBinding(IBinding $binding);
    
    
    
    public function unbind(IBinding $binding) {
        $id = $binding->getId();
        
        if(isset($this->_bindings[$id])) {
            $this->_unregisterBinding($binding);
            unset($this->_bindings[$id]);
            $binding->setEventResource(null)->isAttached(false);
        }
        
        return $this;
    }
    
    public function unbindByName(IListener $listener, $bindingName, $type=halo\event\READ) {
        $id = Binding::createId($listener, $type, $bindingName);
        
        if(isset($this->_bindings[$id])) {
            return $this->unbind($this->_bindings[$id]);
        }
        
        return $this;
    }
    
    public function unbindAll(IListener $listener) {
        foreach($this->_bindings as $binding) {
            if($binding->hasListener($listener)) {
                $this->unbind($binding);
            }
        }
        
        return $this;
    }
    
    abstract protected function _unregisterBinding(IBinding $binding);
    
    
    public function getBinding(IListener $listener, $bindingName, $type=halo\event\READ) {
        $id = Binding::createId($listener, $type, ucfirst($bindingName));
        
        if(isset($this->_bindings[$id])) {
            return $this->_bindings[$id];
        }
        
        throw new BindException(
            'Binding '.$id.' could not be found'
        );
    }
    
    public function getBindings() {
        return $this->_bindings;
    }
    
    public function clearBindings() {
        foreach($this->_bindings as $binding) {
            $this->unbind($binding);
        }
        
        return $this;
    }
    
    public function countBindings() {
        return count($this->_bindings);
    }
    
    public function destroy() {
        $this->_dispatcher->remove($this);
        return $this;
    }
}