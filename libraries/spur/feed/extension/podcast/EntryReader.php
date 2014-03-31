<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\podcast;

use df;
use df\core;
use df\spur;

class EntryReader implements spur\feed\IEntryReaderPlugin {
    
    use spur\feed\TEntryReader;
    
    protected static $_xPathNamespaces = array(
        'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd'
    );
        
    public function getCastAuthor() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:author)'
        );
    }
    
    public function getBlock() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:block)'
        );
    }
    
    public function getDuration() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:duration)'
        );
    }
    
    public function getExplicit() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:explicit)'
        );
    }
    
    public function getKeywords() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:keywords)'
        );
    }
    
    public function getSubtitle() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:subtitle)'
        );
    }
    
    public function getSummary() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:summary)'
        );
    }
}