<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\response;

use df;
use df\core;
use df\halo;

class CookieCollection implements halo\protocol\http\IResponseCookieCollection, core\collection\IMappedCollection, core\IDumpable {
    
    use core\TStringProvider;
    use core\collection\TValueMapArrayAccess;
    
    protected $_set = array();
    protected $_remove = array();
    
    
    public function __construct($input=null) {
        if($input !== null) {
            $this->import($input);
        }
    }
    
    public function __clone() {
        foreach($this->_set as $key => $cookie) {
            $this->_set[$key] = clone $cookie;
        }
        
        foreach($this->_remove as $key => $cookie) {
            $this->_remove[$key] = clone $cookie;
        }
        
        return $this;
    }
    
    public function import($input) {
        if($input instanceof halo\protocol\http\IResponseCookieCollection) {
            foreach($input->_set as $key => $cookie) {
                $this->_set[$key] = clone $cookie;
            }
            
            foreach($input->_remove as $key => $cookie) {
                $this->_remove[$key] = clone $cookie;
            }
        } else {
            if($input instanceof core\IArrayProvider) {
                $input = $input->toArray();
            }
            
            if(is_array($input)) {
                foreach($input as $key => $value) {
                    $this->set($key, $value);
                }
            }
        }
        
        return $this;
    }
    
    public function isEmpty($includeRemoved=false) {
        $output = empty($this->_set);
        
        if($includeRemoved) {
            $output = $output && empty($this->_remove);
        }
        
        return $output;
    }
    
    public function clear() {
        $this->_set = array();
        $this->_remove = array();
    }
    
    public function extract() {
        return array_shift($this->_set);
    }
    
    public function extractList($count) {
        $output = array();
        
        for($i = 0; $i < (int)$count; $i++) {
            $output[] = $this->extract();
        }
        
        return $output;
    }
    
    public function count() {
        return count($this->_set);
    }
    
    public function toArray() {
        return $this->_set;
    }
    
    public function getRemoved() {
        return $this->_remove;
    }
    
    
    public function set($name, $cookie=null) {
        if($name instanceof halo\protocol\http\IResponseCookie) {
            $cookie = $name;
            $name = $cookie->getName();
        }
        
        if(!$cookie instanceof halo\protocol\http\IResponseCookie) {
            $cookie = new Cookie($name, $cookie);
        }
        
        $name = $cookie->getName();
        unset($this->_remove[$name]);
        
        $this->_set[$name] = $cookie;
        return $this;
    }
    
    public function get($name, $default=null) {
        if(isset($this->_set[$name])) {
            return $this->_set[$name];
        }
        
        if(!$default instanceof halo\protocol\http\IResponseCookie) {
            $default = new Cookie($name, $default);
        }
        
        return $default;
    }
    
    public function has($name) {
        if($name instanceof halo\protocol\http\IResponseCookie) {
            $name = $name->getName();
        }
        
        return isset($this->_set[$name]);
    }
    
    public function remove($name) {
        $cookie = null;
        
        if($name instanceof halo\protocol\http\IResponseCookie) {
            $cookie = $name;
            $name = $cookie->getName();
        }
        
        if(isset($this->_set[$name])) {
            $cookie = $this->_set[$name];
        } else if($cookie === null) {
            $cookie = new Cookie($name, 'deleted');
        }
        
        unset($this->_set[$name]);
        $this->_remove[$name] = $cookie;
        
        return $this;
    }
    
    
    public function applyTo(halo\protocol\http\IResponseHeaderCollection $headers) {
        $cookies = $headers->get('Set-Cookie');
        
        if($cookies === null) {
            $cookies = array();
        } else if(!is_array($cookies)) {
            $cookies = array($cookies);
        }
        
        foreach($this->_set as $cookie) {
            $cookies[] = $cookie->toString();
        }
        
        foreach($this->_remove as $cookie) {
            $cookies[] = $cookie->toInvalidateString();
        }
        
        $headers->set('Set-Cookie', array_unique($cookies));
        
        return $this;
    }
    
// Strings
    public function toString() {
        $output = array();
        
        foreach($this->_set as $cookie) {
            $output[] = 'Set-Cookie: '.$cookie->toString();
        }
        
        foreach($this->_remove as $cookie) {
            $output[] = 'Set-Cookie: '.$cookie->toInvalidateString();
        }
        
        return implode("\r\n", $output);
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = array();
        
        foreach($this->_set as $cookie) {
            $output['+ '.$cookie->getName()] = $cookie->toString();
        }
        
        foreach($this->_remove as $cookie) {
            $output['- '.$cookie->getName()] = $cookie->toInvalidateString();
        }
        
        return $output;
    }
}