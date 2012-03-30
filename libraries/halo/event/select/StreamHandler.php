<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\select;

use df;
use df\core;
use df\halo;

class StreamHandler extends HandlerBase implements halo\event\IStreamHandler {
    
    protected $_stream;
    protected $_readCount = 0;
    protected $_writeCount = 0;
    
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
    
    protected function _registerBinding(halo\event\IBinding $binding) {
        switch($binding->getType()) {
            case halo\event\READ:
                $this->_readCount++;
                break;
                
            case halo\event\WRITE:
                $this->_writeCount++;
                break;
                
            case halo\event\READ_WRITE:
                $this->_readCount++;
                $this->_writeCount++;
                break;
                
            case halo\event\TIMEOUT:
                // TODO: handle timeouts
                break;
        }
        
        parent::_registerBinding($binding);
    }
    
    protected function _unregisterBinding(halo\event\IBinding $binding) {
        switch($binding->getType()) {
            case halo\event\READ:
                $this->_readCount--;
                break;
                
            case halo\event\WRITE:
                $this->_writeCount--;
                break;
                
            case halo\event\READ_WRITE:
                $this->_readCount--;
                $this->_writeCount--;
                break;
                
            case halo\event\TIMEOUT:
                // TODO: handle timeouts
                break;
        }
        
        parent::_unregisterBinding($binding);
    }
    
    public function _exportToMap(&$map) {
        $resource = $this->_stream->getStreamDescriptor();
        $id = (int)$resource;
        
        if($this->_readCount) {
            $map[Dispatcher::STREAM][Dispatcher::RESOURCE][Dispatcher::READ][$id] = $resource;
        }
        
        if($this->_writeCount) {
            $map[Dispatcher::STREAM][Dispatcher::RESOURCE][Dispatcher::WRITE][$id] = $resource;
        }
        
        $map[Dispatcher::STREAM][Dispatcher::HANDLER][$id] = $this;
        $map[Dispatcher::COUNTER][$key]++;
    }
}