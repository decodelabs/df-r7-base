<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\reader\atom;

use df;
use df\core;
use df\spur;

class Feed extends spur\feed\reader\Feed {

    protected static $_defaultExtensions = array(
        'atom', 'dublinCore', 'content', 'wellFormedWeb', 'slash', 'thread'
    );
    
    protected function _getXPathPrefix() {
        return '/atom:feed';
    }
    
    protected function _getEntryXPathPrefix($entryKey) {
        if($this->_type == spur\feed\ITypes::ATOM_10
        || $this->_type == spur\feed\ITypes::ATOM_03) {
            return '//atom:entry['.($entryKey+1).']';
        }
        
        return '//item['.($entryKey+1).']';
    }

    public function getTypeName() {
        switch($this->_type) {
            case spur\feed\ITypes::ATOM_03: 
                return 'ATOM 03';
                
            case spur\feed\ITypes::ATOM_10: 
                return 'ATOM 10';
                
            case spur\feed\ITypes::ATOM_10_ENTRY: 
                return 'ATOM 10 Entry';
                
            case spur\feed\ITypes::ATOM: 
            default:
                return 'ATOM';
        }
    }
    
    protected function _getId() {
        return $this->getFromPlugin('atom', 'id');
    }
    
    protected function _getAuthors() {
        return $this->getFromPlugin('atom', 'authors');
    }
    
    protected function _getTitle() {
        return $this->getFromPlugin('atom', 'title');
    }
        
    protected function _getDescription() {
        return $this->getFromPlugin('atom', 'description');
    }
    
    protected function _getImage() {
        return $this->getFromPlugin('atom', 'image');
    }
    
    protected function _getCategories() {
        return $this->getFromPlugin(['atom', 'dublinCore'], 'categories');
    }
    
    protected function _getSourceLink() {
        return $this->getFromPlugin('atom', 'sourceLink');
    }
    
    protected function _getFeedLink() {
        return $this->getFromPlugin('atom', 'feedLink');
    }
    
    protected function _getLanguage() {
        $language = $this->getFromPlugin('atom', 'language');
        
        if(!$language) {
            $language = $this->_xPath->evaluate('string(//@xml:lang[1])');
        }
        
        return $language;
    }
    
    protected function _getCopyright() {
        return $this->getFromPlugin('atom', 'copyright');
    }
    
    protected function _getCreationDate() {
        return $this->getFromPlugin('atom', 'creationDate');
    }
    
    protected function _getLastModifiedDate() {
        return $this->getFromPlugin('atom', 'lastModifiedDate');
    }
    
    protected function _getGenerator() {
        return $this->getFromPlugin('atom', 'generator');
    }
    
    protected function _getHubs() {
        return $this->getFromPlugin('atom', 'hubs');
    }
    
    
    
    protected function _createEntry(\DomElement $domElement, $key) {
        return new Entry($domElement, $this->_xPath, $key, $this->_type);
    }
    
    protected function _getEntryNodeList() {
        return $this->_xPath->query('//atom:entry');
    }
    
    protected function _getXPathNamespaces() {
        switch($this->_type) {
            case spur\feed\ITypes::ATOM_03:
                return ['atom' => spur\feed\INamespaces::ATOM_03];
                
            case spur\feed\ITypes::ATOM_10:
                return ['atom' => spur\feed\INamespaces::ATOM_10];
        }
    }
}