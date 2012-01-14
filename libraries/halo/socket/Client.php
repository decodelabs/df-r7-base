<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket;

use df;
use df\core;
use df\halo;

abstract class Client extends Base implements IClientSocket {
    
    protected static $_defaultOptions = array(
        'connectionTimeout' => null
    );
    
    protected $_isConnected = false;

    public static function factory($address, $useStreams=false) {
        $address = halo\socket\address\Base::factory($address);
        
        if($address instanceof IClientSocket) {
            return $address;
        }
        
        if(!$useStreams && !extension_loaded('sockets')) {
            $useStreams = true;
        }
        
        $class = null;
        $protocol = ucfirst($address->getScheme());
        $nativeClass = 'df\\halo\\socket\\native\\'.$protocol.'Client';
        $streamsClass = 'df\\halo\\socket\\streams\\'.$protocol.'Client';
        
        if(!$useStreams) {
            if(class_exists($nativeClass)) {
                $class = $nativeClass;
            } else if(class_exists($streamsClass)) {
                $class = $streamsClass;
            }
        } else {
            if(class_exists($streamsClass)) {
                $class = $streamsClass;
            } else if($protocol != 'Tcp' && class_exists($nativeClass)) {
                $class = $nativeClass;
            }
        }
        
        if(!$class) {
            throw new RuntimeException(
                'Protocol '.$address->getScheme().', whilst valid, does not yet have a client handler class'
            );
        }
        
        return new $class($address);
    }
    
    protected static function _populateOptions() {
        $output = array_merge(parent::_populateOptions(), self::$_defaultOptions);
        
        if(!isset($output['connectionTimeout']) || $output['connectionTimeout'] === null) {
            $output['connectionTimeout'] = ini_get('default_socket_timeout');
        }
        
        return $output;
    }
    
// Options
    public function setConnectionTimeout($timeout) {
        return $this->_setOption('connectionTimeout', $timeout);
    }
    
    public function getConnectionTimeout() {
        return $this->_getOption('connectionTimeout');
    }
    
    
// Operation
    public function connect() {
        if($this->_isConnected) {
            return $this;
        }
        
        $this->_connectPeer();
        $this->_isConnected = true;
        
        $this->_readingEnabled = true;
        $this->_writingEnabled = true;
        
        return $this;
    }
    
    abstract protected function _connectPeer();
    
    
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