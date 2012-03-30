<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\client;

use df;
use df\core;
use df\halo;

abstract class Client implements IClient {
    
    const PEER_FIRST = 1;
    const CLIENT_FIRST = 2;
    const PEER_STREAM = 3;
    const CLIENT_STREAM = 4;
    const DUPLEX_STREAM = 5;
    
    const BUFFER = null;
    const WRITE = 1;
    const OPEN_WRITE = 2;
    const READ = 3;
    const OPEN_READ = 4;
    const END = 5;
    
    const PROTOCOL_DISPOSITION = self::CLIENT_FIRST;
    
    protected $_isStarted = false;
    protected $_socket;
    protected $_dispatcher;
    
    protected $_readChunkSize = 1024;
    protected $_writeChunkSize = 1024;
    
    public function __construct() {
        
    }
    
    public function setDispatcher(halo\event\IDispatcher $dispatcher) {
        if($this->_isStarted) {
            throw new RuntimeException(
                'You cannot change the dispatcher once the server has started'
            );
        }
        
        $this->_dispatcher = $dispatcher;
        return $this;
    }
    
    public function getDispatcher() {
        if(!$this->_dispatcher) {
            $this->_dispatcher = halo\event\DispatcherBase::factory();
        }
        
        return $this->_dispatcher;
    }
    
    public function getProtocolDisposition() {
        return static::PROTOCOL_DISPOSITION;
    }
    
    public function run() {
        if($this->_isStarted) {
            return $this;
        }
        
        $this->_setup();
        $this->_isStarted = true;
        
        $this->getDispatcher()->start();
    }
    
    protected function _setup() {
        $dispatcher = $this->getDispatcher();
    }
}