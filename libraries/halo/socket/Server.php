<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket;

use df;
use df\core;
use df\halo;

abstract class Server extends Base implements IServerSocket {
    
    protected static $_defaultOptions = array(
        'connectionQueueSize' => 128,
        'reuseAddress' => true
    );
    
    protected $_isListening = false;
    
    public static function factory($address, $useStreams=false) {
        $address = halo\socket\address\Base::factory($address);
        
        if($address instanceof IServerSocket) {
            return $address;
        }
        
        if(!$useStreams 
        && (!extension_loaded('sockets') || $address->getSecureTransport())) {
            $useStreams = true;
        }
        
        //$useStreams = true;
        
        $class = null;
        $protocol = ucfirst($address->getScheme());
        $nativeClass = 'df\\halo\\socket\\native\\'.$protocol.'_Server';
        $streamsClass = 'df\\halo\\socket\\streams\\'.$protocol.'_Server';
        
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
                'Protocol '.$address->getScheme().', whilst valid, does not yet have a server handler class'
            );
        }
        
        return new $class($address);
    }
    
    protected static function _populateOptions() {
        return array_merge(parent::_populateOptions(), self::$_defaultOptions);
    }
    
    public function __construct($address) {
        parent::__construct($address);
        
        if(defined('SOMAXCONN')) {
            $this->_options['connectionQueueSize'] = SOMAXCONN;
        }
    }
    
    
// Options
    public function shouldReuseAddress($flag=null) {
        if($flag === null) {
            return $this->_getOption('reuseAddress', (bool)$flag);
        }
        
        if($this->_isBound) {
            throw new RuntimeException(
                'Can\'t set reuse address option once a server has been bound'
            );
        }
        
        return $this->_setOption('reuseAddress');
    }
    
    
// Operation
    public function listen() {
        if($this->_isListening) {
            return $this;
        }
        
        if($this->_socket === false) {
            throw new RuntimeException(
                'This socket has already been closed'
            );
        }
        
        $this->_startListening();
        $this->_isListening = true;

        if(!$this instanceof IConnectionOrientedSocket) {
            $this->_readingEnabled = true;
            $this->_writingEnabled = true;
        }
        
        return $this;
    }
    
    abstract protected function _startListening();
    
    public function isConnected() {
        return $this->_isListening;
    }

    public function isListening() {
        return $this->_isListening;
    }

    public function shouldBlock($flag=null) {
        if($flag !== null) {
            $this->_shouldBlock = (bool)$flag;
            return $this;
        }

        return $this->_shouldBlock;
    }
    
    public function accept() {
        if(!$this->_isListening) {
            $this->listen();
        }
        
        if($this instanceof ISequenceServerSocket) {
            $socket = $this->_acceptSequencePeer();
            
            if($socket === false) {
                throw new ConnectionException(
                    'Could not accept connection on '.$this->_address.' - '.$this->_getLastErrorMessage()
                );
            }
            
            return ServerPeer::factory($this, $socket, $this->_getPeerAddress($socket))
                ->shouldBlock($this->_shouldBlock);
        } else {
            core\stub('datagram / raw server accept');
        }
    }
    
    abstract protected function _acceptSequencePeer(); 
    abstract protected function _getPeerAddress($socket);
}