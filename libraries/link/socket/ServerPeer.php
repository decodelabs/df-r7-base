<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\socket;

use df;
use df\core;
use df\link;

abstract class ServerPeer extends Base implements IServerPeerSocket, core\IDumpable {
    
    use TIoSocket;

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
        $this->shouldBlock($parent->shouldBlock());
    }
    
    
    public function isConnected() {
        return $this->_isConnected;
    }

    
// Dump
    public function getDumpProperties() {
        $output = $this->getId().' (';
        $args = [];
        
        if($this->_isConnected) {
            if($this->_readingEnabled) {
                $args[] = 'r';
            }
            
            if($this->_writingEnabled) {
                $args[] = 'w';
            }
        }

        if(empty($args)) {
            $args[] = 'x';
        }
        
        if($this->_isSecure) {
            array_unshift($args, 's');
        }
        
        return $output.implode('/', $args).')';
    }
}