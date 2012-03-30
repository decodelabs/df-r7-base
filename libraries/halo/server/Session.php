<?php

namespace df\halo\server;

use df\core;
use df\halo;

class Session implements ISession {

    public $readBuffer = '';
    public $writeBuffer = '';
    
    protected $_writeState = Base::BUFFER;
    protected $_socket;
    
    public function __construct(halo\socket\IServerPeerSocket $socket) {
        $this->_socket = $socket;
    }
    
    public function getId() {
        return $this->_socket->getId();
    }
    
    public function getSocket() {
        return $this->_socket;
    }
    
    public function setWriteState($state) {
        $this->_writeState = $state;
        return $this;
    }
    
    public function getWriteState() {
        return $this->_writeState;
    }
}