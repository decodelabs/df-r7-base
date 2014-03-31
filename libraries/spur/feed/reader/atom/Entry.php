<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\reader\atom;

use df;
use df\core;
use df\spur;

class Entry extends spur\feed\reader\Entry {

    protected $_xPathAtom = '';
    
    public function __construct(\DomElement $domElement, \DomXPath $xPath, $entryKey, $type=null) {
        parent::__construct($domElement, $xPath, $entryKey, $type);
        
        $this->_xPathAtom = '//atom:entry['.($this->_entryKey+1).']';
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
    
    protected function _getContent() {
        return $this->getFromPlugin('atom', 'content');
    }
    
    protected function _getLinks() {
        return $this->getFromPlugin('atom', 'links');
    }
    
    protected function _getCommentCount() {
        return $this->getFromPlugin('thread', 'commentCount');
    }
    
    protected function _getCommentLink() {
        return $this->getFromPlugin('atom', 'commentLink');
    }
    
    protected function _getCommentFeedLink() {
        return $this->getFromPlugin('atom', 'commentFeedLink');
    }
    
    protected function _getCreationDate() {
        return $this->getFromPlugin('atom', 'creationDate');
    }
    
    protected function _getLastModifiedDate() {
        return $this->getFromPlugin('atom', 'lastModifiedDate');
    }
    
    protected function _getEnclosure() {
        return $this->getFromPlugin('atom', 'enclosure');
    }
    
    protected function _getCategories() {
        return $this->getFromPlugin(['atom', 'dublinCore'], 'categories');
    }
}