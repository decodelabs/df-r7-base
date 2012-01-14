<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\uri;

use df;
use df\core;

class MailtoUrl implements IMailtoUrl, core\IDumpable {
    
    use core\TStringProvider;
    use TUrl;
    use TUsernameContainer;
    use TDomainContainer;
    use TQueryContainer;
    
    public static function factory($url) {
        if($url instanceof IMailtoUrl) {
            return $url;
        }
        
        $class = get_called_class();
        return new $class($url);
    }
    
    public function __construct($url=null) {
        if($url !== null) {
            $this->import($url);
        }
    }
    
    public function import($url='') {
        if($url !== null) {
            $this->reset();
        }
        
        if($url == '' || $url === null) {
            return $this;
        }
        
        if($url instanceof self) {
            $this->_username = $url->_username;
            $this->_domain = $url->_domain;
            
            if($url->_query !== null) {
                $this->_query = clone $url->_query;
            }
            
            return $this;
        }
        
        
        if(strtolower(substr($url, 0, 7)) == 'mailto:') {
            $url = ltrim(substr($url, 7), '/');
        }
        
        // Query
        $parts = explode('?', $url, 2);
        $url = array_shift($parts);
        $this->setQuery(array_shift($parts));
        
        $this->setEmail($url);
        
        return $this;
    }
    
    public function reset() {
        $this->_resetUsername();
        $this->_resetDomain();
        
        $this->_query = null;
        
        return $this;
    }
    
// Scheme
    public function getScheme() {
        return 'mailto';
    }
    
    

// Email
    public function setEmail($email) {
        if(strlen($email)) {
            $parts = explode('@', $email);
            $this->setUsername(array_shift($parts));
            $this->setDomain(array_shift($parts));
        } else {
            $this->_resetUsername();
            $this->_resetDomain();
        }
        
        return $this;
    }
    
    public function getEmail() {
        if($this->_username === null || $this->_domain === null) {
            return null;
        }
        
        return $this->_username.'@'.$this->_domain;
    }
    
    public function hasEmail($emails=false) {
        if($emails === false) {
            return $this->_username !== null && $this->_domain !== null;
        }
        
        if(!is_array($emails)) {
            $emails = func_get_args();
        }
        
        return in_array($this->getEmail(), $emails, true);
    }
    
    
    
// Subject
    public function setSubject($subject) {
        if(strlen($subject)) {
            $this->getQuery()->subject = (string)$subject;
        } else if($this->_query) {
            unset($this->_query->subject);
        }
        
        return $this;
    }
    
    public function getSubject() {
        if($this->_query) {
            return $this->_query['subject'];
        }
        
        return null;
    }
    
    public function hasSubject() {
        return $this->_query !== null && isset($this->_query->subject);
    }
    
    
// Strings
    public function toString() {
        $output = 'mailto:'.$this->getEmail();
        $output .= $this->_getQueryString();
        
        return $output;
    }
    
    public function toReadableString() {
        $output = 'mailto:'.$this->getEmail();
        $output .= $this->_getQueryString();
        
        return $output;
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}