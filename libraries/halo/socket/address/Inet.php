<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\address;

use df;
use df\core;
use df\halo;

class Inet extends Base implements IInetAddress {
    
    use core\uri\TUrl_IpContainer;
    use core\uri\TUrl_PortContainer;
    
    
    public static function factory($address) {
        if($address instanceof IInetAddress) {
            return $address;
        }
        
        return new self($address);
    }
    
    public function import($address='') {
        if($address !== null) {
            $this->reset();
        }
        
        if($address == '' || $address === null) {
            return $this;
        }
        
        if($address instanceof IInetAddress) {
            $this->_scheme = $address->_scheme;
            $this->_ip = $address->_ip;
            $this->_port = $address->_ip;
            
            return $this;
        }
        
        
        $parts = explode('://', $address, 2);
        $address = array_pop($parts);
        $this->setScheme(array_shift($parts));
        
        if(isset($address{0}) && $address{0} == '[') {
            // V6
            $parts = explode(']', substr($address, 1), 2);
            $this->setIp(array_shift($parts));
            
            if(isset($parts[0])) {
                $this->setPort(substr(array_shift($parts), 1));
            }
        } else {
            $parts = explode(':', $address, 2);
            $this->setIp(array_shift($parts));
            
            if(isset($parts[0])) {
                $this->setPort(array_shift($parts));
            }
        }
        
        return $this;
    }
    
    public function reset() {
        $this->_resetScheme();
        $this->_resetIp();
        $this->_resetPort();
        
        return $this;
    }
    
    
    public function __get($member) {
        switch($member) {
            case 'scheme':
                return $this->getScheme();
                
            case 'ip':
                return $this->getIp();
                
            case 'port':
                return $this->getPort();
        }
    }
    
    public function __set($member, $value) {
        switch($member) {
            case 'scheme':
                return $this->setScheme($value);
                
            case 'ip':
                return $this->setIp($value);
                
            case 'port':
                return $this->setPort($value);
        }
    }
    
    
// Scheme
    public function setScheme($scheme) {
        if(!strlen($scheme)) {
            $scheme = 'tcp';
        }
        
        $scheme = strtolower($scheme);
        
        switch($scheme) {
            case 'udp':
            case 'tcp':
            case 'icmp':
                $this->_scheme = $scheme;
                break;
                
            default:
                if(false == getprotobyname($scheme)) {
                    throw new halo\socket\InvalidArgumentException(
                        'Protocol '.$scheme.' is not currently supported'
                    );
                }
                
                $this->_scheme = $scheme;
                break;
        }
        
        return $this;
    }
    
    public function getScheme() {
        if(!$this->_scheme) {
            return 'tcp';
        }
        
        return $this->_scheme;
    }
    
    
// Type
    public function getSocketDomain() {
        if($this->getIp()->isV6()) {
            return 'inet6';
        } else {
            return 'inet';
        }
    }
    
    public function getDefaultSocketType() {
        if($this->_protocol == 'tcp') {
            return 'stream';
        } else if($this->_protocol == 'udp') {
            return 'datagram';
        } else {
            return 'raw';
        }
    }

    
    
// Strings
    public function toString($scheme=null) {
        if($scheme === null) {
            $scheme = $this->getScheme();
        }
        
        $output = $scheme.'://';
        $output .= $this->_getIpString();
        $output .= $this->_getPortString();
        
        return $output;
    }
}
