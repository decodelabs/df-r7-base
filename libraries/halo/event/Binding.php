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

    public static function createId($listener, $type, $name) {
        if($listener instanceof IListener) {
            $listenerId = get_class($listener);
        } else if(is_callable($listener)) {
            $listenerId = core\string\Util::getCallableId($listener);
        } else {
            core\dump($listener);
        }

        return $listenerId.':'.$type.':'.$name;
    }
    
    public function __construct(IHandler $handler, $listener, $type, $name, $persistent=false, array $args=null) {
        $this->_listener = $listener;
        $this->_name = ucfirst($name);
        $this->_type = $type;
        
        if(!is_array($args)) {
            if($args === null) {
                $args = [];
            } else {
                $args = [$args];
            }
        }
        
        $this->_args = $args;
        $this->_type = $type;
        $this->_isPersistent = (bool)$persistent;
        $this->_id = self::createId($this->_listener, $this->_type, $this->_name);

        if($this->_listener instanceof IListener) {
            if(!$this->_listener instanceof IAdaptiveListener) {
                $func = 'on'.ucfirst($handler->getScheme()).$this->_name;
            
                if(!method_exists($this->_listener, $func)) {
                    throw new BindException(
                        'Listener method '.$func.' could not be found on listener '.get_class($this->_listener)
                    );
                }
            }
        } else if(!is_callable($this->_listener)) {
            throw new BindException(
                'Listener is not Callable or IListener'
            );
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
    
    public function hasListener($listener) {
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
            if($this->_listener instanceof IAdaptiveListener) {
                $this->_listener->handleEvent($handler, $this);
            } else {
                $args = $this->_args;
                array_unshift($args, $handler, $this);

                if($this->_listener instanceof IListener) {
                    $func = 'on'.ucfirst($handler->getScheme()).$this->_name;
                    $callback = [$this->_listener, $func];
                } else {
                    $callback = $this->_listener;
                }

                call_user_func_array($callback, $args);
            }
        } catch(\Exception $e) {
            core\debug()->exception($e)->flush();
        }
        
        return $this;
    }
}