<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket;

use df;
use df\core;
use df\halo;

abstract class ServerPeer extends Base implements IServerPeerSocket {
    
    protected $_isConnected = true;
    
    public static function factory(IServerSocket $parent, $socket, $address) {
        $class = get_class($parent).'Peer';
        
        if(!class_exists($class)) {
            throw new RuntimeException(
                'Protocol '.$parent->getAddress()->getScheme().', does not have a server peer handler class'
            );
        }
        
        return new $class($parent, $socket, $address);
    }
    
    public function __construct(IServerSocket $parent, $socket, $address) {
        parent::__construct($address);
        
        if(!is_resource($socket)) {
            $this->_isConnected = false;
            $socket = false;
        }
        
        $this->_id = $parent->getAddress().'|'.$this->_address;
        $this->_socket = $socket;
        $this->_options = $parent->getOptions();
        $this->_readingEnabled = true;
        $this->_writingEnabled = true;
    }
    
    
// Operation
    public function peek($length) {
        if(!$this->_readingEnabled) {
            throw new IOException(
                'Reading has already been shut down'
            );
        }
        
        return $this->_peekChunk($length);
    }
    
    public function read($length) {
        if(!$this->_readingEnabled) {
            throw new IOException(
                'Reading has already been shut down'
            );
        }
        
        return $this->_readChunk($length);
    }
    
    public function readAll() {
        if(!$this->_readingEnabled) {
            throw new IOException(
                'Reading has already been shut down'
            );
        }
        
        $data = false;
        
        while(false !== ($read = $this->_readChunk(1024))) {
            $data .= $read;
        }
        
        return $data;
    }
    
    public function write($data) {
        if(!$this->_writingEnabled) {
            throw new IOException(
                'Writing has already been shut down'
            );
        }
        
        return $this->_writeChunk($data);
    }
    
    public function writeAll($data) {
        if(!$this->_writingEnabled) {
            throw new IOException(
                'Writing has already been shut down'
            );
        }
        
        if(!$length = strlen($data)) {
            return $this;
        }
        
        for($written = 0; $written < $length; $written += $result) {
            $result = $this->_writeChunk(substr($data, $written));
            
            if($result === false) {
                throw new IOException(
                    'Unable to write to '.$this->_address.' - '.$this->_getLastErrorMessage()
                );
            }
        }
        
        return $this;
    }
    
    abstract protected function _peekChunk($length);
    abstract protected function _readChunk($length);
    abstract protected function _writeChunk($data);
}