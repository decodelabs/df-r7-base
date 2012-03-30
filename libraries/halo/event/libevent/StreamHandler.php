<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

class StreamHandler extends HandlerBase implements halo\event\IStreamHandler {
    
    protected $_stream;
    
    public function __construct(IDispatcher $dispatcher, core\io\stream\IStream $stream) {
        parent::__construct($dispatcher);
        $this->_stream = $stream;
    }
    
    public function getId() {
        return halo\event\DispatcherBase::getStreamHandlerId($this->_stream);
    }
    
    public function getStream() {
        return $this->_stream;
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
        return $this->_stream->getStreamDescriptor();
    }
}