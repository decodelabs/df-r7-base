<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\uri;

use df;
use df\core;

trait TUrl {
    
}


trait TTransientSchemeUrl {
    
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
    
    private function _resetScheme() {
        $this->_scheme = null;
    }
    
    protected function _getSchemeString() {
        if($this->_scheme !== null) {
            return $this->_scheme.'://';
        }
    }
}



// Credentials
trait TUsernameContainer {
     
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
    
    private function _resetUsername() {
        $this->_username = null;
    }
}


// Domain
trait TDomainContainer {
    
    protected $_domain;
    
    public function setDomain($domain) {
        $this->_domain = (string)$domain;
        return $this;
    }
    
    public function getDomain() {
        return $this->_domain;
    }
    
    private function _resetDomain() {
        $this->_domain = null;
    }
}


// Path
trait TPathContainer {
    
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
    
    public function getReadablePathString() {
        if($this->_path) {
            return $this->_path->toString();
        } else {
            return '/';
        }
    }
    
    public function hasPath() {
        return $this->_path !== null;
    }
    
    private function _clonePath() {
        if($this->_path) {
            $this->_path = clone $this->_path;
        }
    }
    
    private function _resetPath() {
        $this->_path = null;
    }
    
    protected function _getPathString() {
        if($this->_path !== null) {
            return $this->_path->toUrlEncodedString();
        }
    }
    
    protected function _getReadablePathString() {
        if($this->_path !== null) {
            return $this->_path->toString();
        }
    }
}


// Query
trait TQueryContainer {
    
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
    
    private function _cloneQuery() {
        if($this->_query) {
            $this->_query = clone $this->_query;
        }
    }
    
    private function _resetQuery() {
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
trait TFragmentContainer {
    
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
    
    private function _resetFragment() {
        $this->_fragment = null;
    }
    
    protected function _getFragmentString() {
        if($this->_fragment !== null) {
            return '#'.$this->_fragment;
        }
    }
}
