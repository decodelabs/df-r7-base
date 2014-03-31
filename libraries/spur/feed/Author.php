<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed;

use df;
use df\core;
use df\spur;

class Author implements IAuthor {
    
    protected $_name;
    protected $_email;
    protected $_url;
    
    public function __construct($name=null, $email=null, $url=null) {
        $this->setName($name);
        $this->setEmail($email);
        $this->setUrl($url);
    }
    
    public function setName($name) {
        $this->_name = trim($name);
        
        if(!strlen($this->_name)) {
            $this->_name = null;
        }
        
        return $this;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function hasName() {
        return $this->_name !== null;
    }
    
    public function setEmail($email) {
        $this->_email = trim($email);
        
        if(!strlen($this->_email)) {
            $this->_email = null;
        }
        
        return $this;
    }
    
    public function getEmail() {
        return $this->_email;
    }
    
    public function hasEmail() {
        return $this->_email !== null;
    }
    
    public function setUrl($url) {
        $this->_url = trim($url);
        
        if(!strlen($this->_url)) {
            $this->_url = null;
        }
        
        return $this;
    }
    
    public function getUrl() {
        return $this->_url;
    }
    
    public function hasUrl() {
        return $this->_url !== null;
    }
    
    public function isValid() {
        return $this->_name || $this->_email || $this->_url;
    }
}