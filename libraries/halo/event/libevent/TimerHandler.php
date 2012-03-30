<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

class TimerHandler extends HandlerBase implements halo\event\ITimerHandler {
    
    protected $_time;
    
    public function __construct(IDispatcher $dispatcher, core\time\IDuration $time) {
        parent::__construct($dispatcher);
        $this->_time = $time;
    }
    
    public function getId() {
        return halo\event\DispatcherBase::getTimerHandlerId($this->_time);
    }
    
    public function getTime() {
        return $this->_time;
    }
    
    public function bind(halo\event\IListener $listener, $bindingName, $persistent=false, array $args=null) {
        return $this->_bind(new halo\event\Binding($this, $listener, halo\event\TIMEOUT, $bindingName, $persistent, $args));
    }
    
    public function getBinding(halo\event\IListener $listener, $bindingName, $type=halo\event\TIMEOUT) {
        return parent::getBinding($listener, $bindingName, halo\event\TIMEOUT);
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