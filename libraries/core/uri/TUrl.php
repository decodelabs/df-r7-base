<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\uri;

use df;
use df\core;
use df\halo;


trait TUrl_TransientScheme {
    
    protected $_scheme;
    
    public function setScheme($scheme) {
        if(!empty($scheme)) {
            $this->_scheme = (string)$scheme;
        } else {
            $this->_scheme = null;
        }
        
        return $this;
    }
    
    public function getScheme() {
        return $this->_scheme;
    }
    
    public function hasScheme() {
        return $this->_scheme !== null;
    }
    
    protected function _resetScheme() {
        $this->_scheme = null;
    }
    
    protected function _getSchemeString() {
        if($this->_scheme !== null) {
            return $this->_scheme.'://';
        }
    }
}



// Credentials
trait TUrl_UsernameContainer {
     
     protected $_username;
     
     public function setUsername($username) {
        if(strlen($username)) {
            $this->_username = (string)$username;   
        } else {
            $this->_username = null;
        }
        
        return $this;
    }
    
    public function getUsername() {
        return $this->_username;
    }
    
    public function hasUsername($usernames=false) {
        if($usernames === false) {
            return $this->_username !== null;
        }
        
        if(!is_array($usernames)) {
            $usernames = func_get_args();
        }
        
        return in_array($this->_username, $usernames, true);
    }
    
    protected function _resetUsername() {
        $this->_username = null;
    }
}

trait TUrl_PasswordContainer {
    
    protected $_password;
    
    public function setPassword($password) {
        if(strlen($password)) {
            $this->_password = (string)$password;
        } else {
            $this->_password = null;
        }
        
        return $this;
    }
    
    public function getPassword() {
        return $this->_password;
    }
    
    public function hasPassword($passwords=false) {
        if($passwords === false) {
            return $this->_password !== null;
        }
        
        if(!is_array($passwords)) {
            $passwords = func_get_args();
        }
        
        return in_array($this->_password, $passwords, true);
    }
    
    protected function _resetPassword() {
        $this->_password = null;
    }
}

trait TUrl_CredentialContainer {
    
    use TUrl_UsernameContainer;
    use TUrl_PasswordContainer;
    
    public function setCredentials($username, $password) {
        return $this->setUsername($username)
            ->setPassword($password);
    }
    
    public function hasCredentials() {
        return $this->_username !== null || $this->_password !== null;
    }
    
    protected function _resetCredentials() {
        $this->_resetUsername();
        $this->_resetPassword();
    }
    
    protected function _getCredentialString() {
        if($this->_username === null && $this->_password === null) {
            return null;
        }
        
        $output = $this->_username;

        if($this->_password !== null) {
            $output .= ':'.$this->_password;
        }
        
        return $output.'@';
    }
}


// Domain
trait TUrl_DomainContainer {
    
    protected $_domain;
    
    public function setDomain($domain) {
        $this->_domain = (string)$domain;
        return $this;
    }
    
    public function getDomain() {
        return $this->_domain;
    }
    
    public function isAbsolute() {
        return (bool)strlen($this->_domain);
    }
    
    protected function _resetDomain() {
        $this->_domain = null;
    }
    
    public function lookupIp() {
        if(($ip = gethostbyname($this->_domain)) == $this->_domain) {
            throw new RuntimeException(
                'Could not lookup IP for '.$this->_domain
            );
        }        
        
        return new halo\Ip($ip);
    }
}


// Ip
trait TUrl_IpContainer {
    
    protected $_ip;
    
    public function setIp($ip) {
        if($ip !== null) {
            $ip = halo\Ip::factory($ip); 
        }
        
        $this->_ip = $ip;
        return $this;
    }
    
    public function getIp() {
        if(!$this->_ip) {
            $this->_ip = halo\Ip::getV4Loopback();
        }
        
        return $this->_ip;
    }
    
    protected function _resetIp() {
        $this->_ip = null;
    }
    
    protected function _getIpString() {
        $ip = $this->getIp();
        
        if($ip->isStandardV4()) {
            return $ip->getV4String();
        } else {
            return '['.$ip->getCompressedV6String().']';
        }
    }
}


// Port
trait TUrl_PortContainer {
    
    protected $_port;
    
    public function setPort($port) {
        if(!empty($port)) {
            $this->_port = (int)$port;
        } else {
            $this->_port = null;
        }
        
        return $this;
    }
    
    public function getPort() {
        return $this->_port;
    }
    
    public function hasPort($ports=false) {
        if($ports === false) {
            return $this->_port !== null;
        }
        
        if(!is_array($ports)) {
            $ports = func_get_args();
        }
        
        return in_array($this->_port, $ports, true);
    }
    
    protected function _resetPort() {
        $this->_port = null;
    }
    
    protected function _getPortString($skip=null) {
        if($this->_port !== null && $this->_port !== $skip) {
            return ':'.$this->_port;
        }
    }
    
    public function getHostString() {
        return $this->getDomain().$this->_getPortString();
    }
}


// Path
trait TUrl_PathContainer {
    
    protected $_path;
    
    public function setPath($path) {
        if(empty($path)) {
            $this->_path = null;
        } else {
            $this->_path = Path::factory($path);
        }
            
        return $this;
    }
    
    public function getPath() {
        if(!$this->_path) {
            $this->_path = new Path();
        }
        
        return $this->_path;
    }
    
    public function getPathString() {
        if($this->_path) {
            return $this->_path->toUrlEncodedString();
        } else {
            return '/';
        }
    }
    
    public function hasPath() {
        return $this->_path !== null;
    }
    
    protected function _clonePath() {
        if($this->_path) {
            $this->_path = clone $this->_path;
        }
    }
    
    protected function _resetPath() {
        $this->_path = null;
    }
    
    protected function _getPathString($absolute=false) {
        if($this->_path !== null) {
            $output = $this->_path->toUrlEncodedString();
            
            if($absolute) {
                $output = '/'.ltrim($output, '/.');
            }
            
            return $output;
        } else if($absolute) {
            return '/';
        }
    }
}


// Query
trait TUrl_QueryContainer {
    
    protected $_query;
    
    public function setQuery($query) {
        if(empty($query)) {
            $this->_query = null;
        } else {
            if(is_string($query)) {
                $query = core\collection\Tree::fromArrayDelimitedString($query);
            } else if(!$query instanceof core\collection\ITree) {
                $query = new core\collection\Tree($query);
            }
            
            $this->_query = $query;
        }
        
        return $this;
    }
    
    public function getQuery() {
        if(!$this->_query) {
            $this->_query = new core\collection\Tree();
        }
        
        return $this->_query;
    }
    
    public function getQueryString() {
        if($this->_query) {
            return $this->_query->toArrayDelimitedString();
        } else {
            return '';
        }
    }
    
    public function hasQuery() {
        return $this->_query !== null;
    }
    
    protected function _cloneQuery() {
        if($this->_query) {
            $this->_query = clone $this->_query;
        }
    }
    
    protected function _resetQuery() {
        $this->_query = null;
    }
    
    protected function _getQueryString() {
        if($this->_query !== null) {
            $queryString = $this->_query->toArrayDelimitedString();
            
            if(!empty($queryString)) {
                return '?'.$queryString;
            }
        }
    }
}



// Fragment
trait TUrl_FragmentContainer {
    
    protected $_fragment;
    
    public function getFragment() {
        return $this->_fragment;
    }

    public function setFragment($fragment) {
        $this->_fragment = (string)$fragment;
        
        if(!strlen($this->_fragment)) {
            $this->_fragment = null;
        }
        
        return $this;
    }
    
    public function hasFragment($fragments=false) {
        if($fragments === false) {
            return $this->_fragment !== null;
        }
        
        if(!is_array($fragments)) {
            $fragments = func_get_args();
        }
        
        return in_array($this->_fragments, $fragments, true);
    }
    
    public function isJustFragment() {
        return $this->_path === null
            && $this->_query === null
            && $this->_fragment !== null;
    }
    
    protected function _resetFragment() {
        $this->_fragment = null;
    }
    
    protected function _getFragmentString() {
        if($this->_fragment !== null) {
            return '#'.$this->_fragment;
        }
    }
}
