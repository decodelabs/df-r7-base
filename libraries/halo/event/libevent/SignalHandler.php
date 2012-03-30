<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

class SignalHandler extends HandlerBase implements halo\event\ISignalHandler {
    
    protected $_signal;
    
    public function __construct(IDispatcher $dispatcher, $signal) {
        parent::__construct($dispatcher);
        $this->_signal = $signal;
    }
    
    public function getId() {
        return halo\event\DispatcherBase::getSignalHandlerId($this->_signal);
    }
    
    public function getSignal() {
        return $this->_signal;
    }
    
    public function bindTimeout(halo\event\IListener $listener, $bindingName, $persistent=false, array $args=null) {
        return $this->_bind(new halo\event\Binding($this, $listener, halo\event\TIMEOUT, $bindingName, $persistent, $args));
    }
    
    public function setTimeout(core\time\IDuration $time) {
        core\stub();
    }
    
    public function getTimeout() {
        core\stub();
    }
    
    public function hasTimeout() {
        core\stub();
    }   

    protected function _getEventTarget() {
        return STDIN;
    }
}