<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;

class Context implements IContext {
    
    protected $_baseDn;
    protected $_upnDomain;
    protected $_controllerDomain;

    public static function factory($baseDn, $upnDomain=null) {
        if($baseDn instanceof IContext) {
            return $baseDn;
        }

        return new self($baseDn, $upnDomain);
    }

    public function __construct($baseDn, $upnDomain=null) {
        if(empty($upnDomain)) {
            $upnDomain = null;
        }

        $this->setBaseDn($baseDn);
        $this->setUpnDomain($upnDomain);
    }

    public function setBaseDn($baseDn) {
        $this->_baseDn = Dn::factory($baseDn);
        return $this;
    }
    
    public function getBaseDn() {
        return clone $this->_baseDn;
    }
    
    public function setControllerDomain($domain) {
        $this->_controllerDomain = strtoupper($domain);
        return $this;
    }
    
    public function getDomain() {
        if($this->_controllerDomain) {
            return $this->_controllerDomain;
        }
        
        return strtoupper($this->_baseDn->getFirstEntry('dc'));
    }
    
    public function setUpnDomain($domain=null) {
        $this->_upnDomain = $domain;
        return $this;
    }
    
    public function getUpnDomain() {
        if(!$this->_upnDomain) {
            return implode('.', $this->_baseDn->getAllEntries('dc'));
        }

        return $this->_upnDomain;
    }
}