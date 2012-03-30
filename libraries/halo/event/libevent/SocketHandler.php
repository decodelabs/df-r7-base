<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

class SocketHandler extends HandlerBase implements halo\event\ISocketHandler {
    
    protected $_socket;
    
    public function __construct(IDispatcher $dispatcher, halo\socket\ISocket $socket) {
        parent::__construct($dispatcher);
        
        $this->_socket = $socket;
    }
    
    public function getId() {
        return halo\event\DispatcherBase::getSocketHandlerId($this->_socket);
    }
    
    public function getSocket() {
        return $this->_socket;
    }
    
    public function newBuffer() {
        core\stub();
    }
    
    public function bindWrite(halo\event\IListener $listener, $bindingName, $persistent=false, array $args=null) {
        return $this->_bind(new halo\event\Binding($this, $listener, halo\event\WRITE, $bindingName, $persistent, $args));
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
        return $this->_socket->getSocketDescriptor();
    }
}