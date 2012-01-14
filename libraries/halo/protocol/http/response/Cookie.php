<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\http\response;

use df;
use df\core;
use df\halo;

class Cookie implements halo\protocol\http\IResponseCookie {
    
    use core\TStringProvider;
    
    protected $_name;
    protected $_value;
    protected $_maxAge;
    protected $_expiryDate;
    protected $_domain;
    protected $_path;
    protected $_isSecure = false;
    protected $_isHttpOnly = false;
    
    public function __construct($name, $value) {
        $this->setName($name);
        $this->setValue($value);
    }
    
    public function setName($name) {
        $this->_name = (string)$name;
        return $this;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    
    public function setValue($value) {
        $this->_value = (string)$value;
        return $this;
    }
    
    public function getValue() {
        return $this->_value;
    }
    
    
    public function setMaxAge(core\time\IDuration $age=null) {
        $this->_maxAge = $age;
        return $this;
    }
    
    public function getMaxAge() {
        return $this->_maxAge;
    }
    
    
    public function setExpiryDate(core\time\IDate $date=null) {
        $this->_expiryDate = $date;
        return $this;
    }
    
    public function getExpiryDate() {
        return $this->_expiryDate;
    }
    
    
    public function setDomain($domain) {
        $this->_domain = $domain;
        return $this;
    }
    
    public function getDomain() {
        return $this->_domain;
    }
    
    
    public function setPath($path) {
        $this->_path = $path;
        return $this;
    }
    
    public function getPath() {
        return $this->_path;
    }
    
    public function setBaseUrl(halo\protocol\http\IUrl $url) {
        $this->setDomain($url->getDomain());
        
        $path = clone $url->getPath();
        $this->setPath($path->isAbsolute(true)->toString());
        
        return $this;
    }
    
    
    public function isSecure($flag=null) {
        if($flag !== null) {
            $this->_isSecure = (bool)$flag;
            return $this;
        }
        
        return $this->_isSecure;
    }
    
    public function isHttpOnly($flag=null) {
        if($flag !== null) {
            $this->_isHttpOnly = (bool)$flag;
            return $this;
        }
        
        return $this->_isHttpOnly;
    }
    
// String
    public function toString() {
        $output = $this->_name.'='.urlencode($this->_value);
        
        if($this->_maxAge) {
            $output .= '; Max-Age='.$this->_maxAge->getSeconds();
        }
        
        if($this->_expiryDate) {
            $output .= '; Expires='.$this->_expiryDate->toTimezone('GMT')->format(core\time\Date::COOKIE);
        }
        
        if($this->_domain !== null) {
            $output .= '; Domain='.$this->_domain;
        }
        
        if($this->_path !== null) {
            $output .= '; Path='.$this->_path;
        }
        
        if($this->_isSecure) {
            $output .= '; Secure';
        }
        
        if($this->_isHttpOnly) {
            $output .= '; HttpOnly';
        }
        
        return $output;
    }
    
    public function toInvalidateString() {
        $output = $this->_name.'=deleted';
        $output .= '; Expires='.core\time\Date::factory('-10 years', 'GMT')->format(core\time\Date::COOKIE);
        
        if($this->_domain !== null) {
            $output .= '; Domain='.$this->_domain;
        }
        
        if($this->_path !== null) {
            $output .= '; Path='.$this->_path;
        }
        
        if($this->_isSecure) {
            $output .= '; Secure';
        }
        
        if($this->_isHttpOnly) {
            $output .= '; HttpOnly';
        }
        
        return $output;
    }
}