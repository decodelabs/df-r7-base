<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\socket;

use df;
use df\core;
use df\link;

abstract class Base implements ISocket {
    
    protected static $_populatedOptions = null;
    protected static $_defaultOptions = [
        'sendBufferSize' => null,
        'receiveBufferSize' => null,
        'sendLowWaterMark' => null,
        'receiveLowWaterMark' => null,
        'sendTimeout' => null,
        'receiveTimeout' => null
    ];
    
    protected $_id;
    protected $_sessionId;
    protected $_address;
    protected $_socket;
    protected $_readingEnabled = false;
    protected $_writingEnabled = false;
    protected $_shouldBlock = false;
    protected $_options = [];
    
    public function __construct($address) {
        $this->_address = link\socket\address\Base::factory($address);
        
        if(static::$_populatedOptions === null) {
            self::$_populatedOptions = static::_populateOptions();
        }
        
        $this->_options = self::$_populatedOptions;

        if(($security = $this->_address->getSecureTransport())
        && $this instanceof ISecureConnectingSocket) {
            $this->setSecureTransport($security);
        }
    }
    
    protected static function _populateOptions() {
        return self::$_defaultOptions;
    }
    
    public function getId() {
        if($this->_id === null) {
            $this->_id = (string)$this->_address;
        }
        
        return $this->_id;
    }
    
    public function getSocketDescriptor() {
        if(!$this->_socket) {
            throw new RuntimeException(
                'This socket has not been activated yet'
            );
        }
        
        return $this->_socket;
    }
    
    public function setSessionId($id) {
        $this->_sessionId = $id;
        return $this;
    }
    
    public function getSessionId() {
        return $this->_sessionId;
    }
    
    public function getAddress() {
        return $this->_address;
    }
    

// Options
    public function getOptions() {
        return $this->_options;
    }
    
    public function setSendBufferSize($buffer) {
        return $this->_setOption('sendBufferSize', $buffer);
    }
    
    public function getSendBufferSize() {
        return $this->_getOption('sendBufferSize');
    }
    
    public function setReceiveBufferSize($buffer) {
        return $this->_setOption('receiveBufferSize', $buffer);
    }
    
    public function getReceiveBufferSize() {
        return $this->_getOption('receiveBufferSize');
    }
    
    
    public function setSendLowWaterMark($bytes) {
        return $this->_setOption('sendLowWaterMark', $bytes);
    }
    
    public function getSendLowWaterMark() {
        return $this->_getOption('sendLowWaterMark');
    }
    
    public function setReceiveLowWaterMark($bytes) {
        return $this->_setOption('receiveLowWaterMark', $bytes);
    }
    
    public function getReceiveLowWaterMark() {
        return $this->_getOption('receiveLowWaterMark');
    }
    
    
    public function setSendTimeout($timeout) {
        return $this->_setOption('sendTimeout', $timeout);
    }
    
    public function getSendTimeout() {
        return $this->_getOption('sendTimeout');
    }
    
    public function setReceiveTimeout($timeout) {
        return $this->_setOption('receiveTimeout', $timeout);
    }
    
    public function getReceiveTimeout() {
        return $this->_getOption('receiveTimeout');
    }
    
    protected function _setOption($option, $value) {
        $this->_options[$option] = $value;
        return $this;
    }
    
    protected function _getOption($option) {
        if(isset($this->_options[$option])) {
            return $this->_options[$option];
        }
        
        return null;
    }
    
    
// State
    public function isActive() {
        return (bool)$this->_socket;
    }
    
    public function isReadingEnabled() {
        return $this->_readingEnabled;
    }
    
    public function isWritingEnabled() {
        return $this->_writingEnabled;
    }
    
    public function shouldBlock($flag=null) {
        if($flag !== null) {
            $this->_shouldBlock = (bool)$flag;

            if($this->_socket) {
                $this->_setBlocking($this->_shouldBlock);
            }

            return $this;
        }

        return $this->_shouldBlock;
    }

    abstract protected function _setBlocking($flag);
    
// Operation
    public function shutdownReading() {
        if(!$this->_socket) {
            throw new RuntimeException(
                'Can\'t shutdown reading, the socket has not been created yet'
            );
        }
        
        if($this->_readingEnabled) {
            $this->_shutdownReading();
        }
        
        $this->_readingEnabled = false;
        return $this;
    }
    
    abstract protected function _shutdownReading();
    
    public function shutdownWriting() {
        if(!$this->_socket) {
            throw new RuntimeException(
                'Can\'t shutdown writing, the socket has not been created yet'
            );
        }
        
        if($this->_writingEnabled) {
            $this->_shutdownWriting();
        }
        
        $this->_writingEnabled = false;
        return $this;
    }
    
    abstract protected function _shutdownWriting();
    
    public function close() {
        if(!$this->_socket) {
            return $this;
        }
        
        if($this->_readingEnabled) {
            $this->shutdownReading();
        }
        
        if($this->_writingEnabled) {
            $this->shutdownWriting();
        }
        
        $this->_closeSocket();
        $this->_socket = false;
        
        return $this;
    }
    
    abstract protected function _closeSocket();
    
    public function __destruct() {
        $this->close();
    }
    
    abstract protected function _getLastErrorMessage();
}