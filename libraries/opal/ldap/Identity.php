<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;

class Identity implements IIdentity {
    
    protected $_uidUsername;
    protected $_uidDomain;
    protected $_upnUsername;
    protected $_upnDomain;
    protected $_password;

    public static function factory($username, $password=null, $domain=null) {
        if($username instanceof IIdentity) {
            return $username;
        }

        return new self($username, $password, $domain);
    }

    public function __construct($username, $password=null, $domain=null) {
        $this->setUsername($username);
        $this->setPassword($password);

        if($domain) {
            if($this->_uidDomain && $this->_uidDomain != strtoupper($domain)) {
                throw new DomainException(
                    'Identity does not match domain '.$domain
                );
            }

            $this->setUidDomain($domain);
        }
    }

    public function setUsername($username) {
        if(false !== strpos($username, '@')) {
            return $this->setUpn($username);
        }
        
        if(false !== strpos($username, '\\')) {
            return $this->setUid($username);
        }
        
        return $this->setUidUsername($username);
    }

    public function getUsername() {
        if($this->_uidUsername) {
            $output = $this->_uidUsername;

            if($this->_uidDomain && false === strpos($output, '=')) {
                $output = $this->_uidDomain.'\\'.$output;
            }

            return $output;
        } else if($this->_upnUsername) {
            $output = $this->_upnUsername;

            if($this->_upnDomain) {
                $output .= '@'.$this->_upnDomain;
            }

            return $output;
        }
        
        return null;
    }
    
    public function setUid($username) {
        $parts = explode('\\', $username);
        $this->setUidDomain(array_shift($parts));
        $this->setUidUsername(array_shift($parts));
        return $this;
    }
    
    public function setUidDomain($domain) {
        $this->_uidDomain = strtoupper($domain);
        return $this;
    }
    
    public function getUidDomain() {
        return $this->_uidDomain;
    }
    
    public function setUidUsername($username) {
        $this->_uidUsername = $username;
        return $this;
    }
    
    public function getUidUsername() {
        return $this->_uidUsername;
    }
    
    public function hasUid() {
        return $this->_uidUsername !== null;
    }
    
    
    public function setUpn($upn) {
        $parts = explode('@', $upn);
        $this->setUpnUsername(array_shift($parts));
        $this->setUpnDomain(array_shift($parts));
        return $this;
    }
    
    public function getUpn() {
        if(!$this->_upnUsername) {
            return null;
        }
        
        $output = $this->_upnUsername;
        
        if($this->_upnDomain) {
            $output .= '@'.$this->_upnDomain;
        }
        
        return $output;
    }
    
    public function setUpnUsername($username) {
        $this->_upnUsername = $username;
        return $this;
    }
    
    public function getUpnUsername() {
        return $this->_upnUsername;
    }
    
    public function setUpnDomain($domain) {
        $this->_upnDomain = $domain;
        return $this;
    }
    
    public function getUpnDomain() {
        return $this->_upnDomain;
    }
    
    public function hasUpn() {
        return $this->_upnUsername !== null;
    }
    
    
    public function setPassword($password) {
        $this->_password = $password;
        return $this;
    }
    
    public function getPassword() {
        return $this->_password;
    }
}