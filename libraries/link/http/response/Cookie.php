<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;

class Cookie implements link\http\IResponseCookie {
    
    use core\TStringProvider;
    
    protected $_name;
    protected $_value;
    protected $_expiryDate;
    protected $_domain;
    protected $_path;
    protected $_isSecure = false;
    protected $_isHttpOnly = false;

    public static function fromString($string) {
        $parts = explode(';', $string);
        $main = explode('=', trim(array_shift($parts)), 2);
        $output = new self(array_shift($main), array_shift($main));
        $hasMaxAge = false;

        foreach($parts as $part) {
            $set = explode('=', trim($part), 2);
            $key = strtolower(array_shift($set));
            $value = trim(array_shift($set));

            switch($key) {
                case 'max-age':
                    $output->setMaxAge($value);
                    $hasMaxAge = true;
                    break;

                case 'expires':
                    if(!$hasMaxAge) {
                        $output->setExpiryDate($value);
                    }
                    break;

                case 'domain':
                    $output->setDomain($value);
                    break;

                case 'path':
                    $output->setPath($value);
                    break;

                case 'secure':
                    $output->isSecure(true);
                    break;

                case 'httponly':
                    $output->isHttpOnly(true);
                    break;
            }
        }
        
        return $output;
    }
    
    public function __construct($name, $value, $expiry=null, $httpOnly=null, $secure=null) {
        $this->setName($name);
        $this->setValue($value);

        if($expiry !== null) {
            $this->setExpiryDate($expiry);
        }

        if($httpOnly !== null) {
            $this->isHttpOnly((bool)$httpOnly);
        }

        if($secure !== null) {
            $this->isSecure((bool)$secure);
        }
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
    
    
    public function setMaxAge($age=null) {
        if(!empty($age)) {
            $this->setExpiryDate(core\time\Date::factory()->add($age));
        } else {
            $this->setExpiryDate(null);
        }

        return $this;
    }
    
    public function getMaxAge() {
        if(!$this->_expiryDate) {
            return null;
        }

        return $this->_expiryDate->toTimestamp() - time();
    }
    
    
    public function setExpiryDate($date=null) {
        if(!empty($date)) {
            $date = core\time\Date::factory($date);
        } else {
            $date = null;
        }

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
    
    public function setBaseUrl(link\http\IUrl $url) {
        //$this->setDomain($url->getDomain());
        
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