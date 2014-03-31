<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed;

use df;
use df\core;
use df\spur;

class Enclosure implements IEnclosure {
    
    protected $_url;
    protected $_length;
    protected $_type;
    
    public function __construct($url, $length, $type) {
        $this->setUrl($url);
        $this->setLength($length);
        $this->setType($type);
    }
    
    public function setUrl($url) {
        $this->_url = $url;
        return $this;
    }
    
    public function getUrl() {
        return $this->_url;
    }
    
    public function setLength($length) {
        $this->_length = $length;
        return $this;
    }
    
    public function getLength() {
        return $this->_length;
    }
    
    public function setType($type) {
        $this->_type = $type;
        return $this;
    }
    
    public function getType() {
        return $this->_type;
    }
}