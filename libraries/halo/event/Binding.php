<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;

class Binding implements IBinding {
    
    protected $_name;
    protected $_id;
    protected $_args;
    protected $_type;
    protected $_isPersistent;
    protected $_isAttached = false;
    protected $_eventResource = null;
    protected $_listener;
    protected $_isAdaptive = false;
    
    public static function createId(IListener $listener, $type, $name) {
        return get_class($listener).':'.$type.':'.$name;
    }
    
    public function __construct(IHandler $handler, IListener $listener, $type, $name, $persistent=false, array $args=null) {
        $this->_listener = $listener;
        $this->_name = ucfirst($name);
        $this->_type = $type;
        
        if(!is_array($args)) {
            if($args === null) {
                $args = array();
            } else {
                $args = array($args);
            }
        }
        
        $this->_args = $args;
        $this->_type = $type;
        $this->_isPersistent = (bool)$persistent;
        $this->_id = self::createId($this->_listener, $this->_type, $this->_name);
        $this->_isAdaptive = $this->_listener instanceof IAdaptiveListener;
        
        if(!$this->_isAdaptive) {
            $func = 'on'.ucfirst($handler->getScheme()).$this->_name;
            
            if(!method_exists($this->_listener, $func)) {
                throw new BindException(
                    'Listener method '.$func.' could not be found on listener '.get_class($this->_listener)
                );
            }
        }
    }
    
    public function getId() {
        return $this->_id;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function hasName($name) {
        return $this->_name == ucfirst($name);
    }
    
    public function isAttached($flag=null) {
        if($flag !== null) {
            $this->_isAttached = (bool)$flag;
            return $this;
        }
        
        return $this->_isAttached;
    }
    
    public function getArgs() {
        return $this->_args;
    }
    
    public function getType() {
        return $this->_type;
    }
    
    public function getListener() {
        return $this->_listener;
    }
    
    public function hasListener(IListener $listener) {
        return $this->_listener === $listener;
    }
    
    public function isPersistent() {
        return $this->_isPersistent;
    }
    
    
    public function setEventResource($resource) {
        $this->_eventResource = $resource;
        return $this;
    }
    
    public function getEventResource() {
        return $this->_eventResource;
    }
    
    public function trigger(IHandler $handler) {
        try {
            if($this->_isAdaptive) {
                $this->_listener->handleEvent($handler, $this);
            } else {
                $func = 'on'.ucfirst($handler->getScheme()).$this->_name;
                $args = $this->_args;
                array_unshift($args, $handler, $this);
                call_user_func_array(array($this->_listener, $func), $args);
            }
        } catch(\Exception $e) {
            core\debug()->exception($e)->flush();
        }
        
        return $this;
    }
}