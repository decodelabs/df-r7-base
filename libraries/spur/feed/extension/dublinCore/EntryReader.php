<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\dublinCore;

use df;
use df\core;
use df\spur;

class EntryReader implements spur\feed\IEntryReaderPlugin {
    
    use spur\feed\TEntryReader;
    
    protected static $_xPathNamespaces = array(
        'dc10' => 'http://purl.org/dc/elements/1.0/',
        'dc11' => 'http://purl.org/dc/elements/1.1/'
    );
        
    public function getId() {
        $id = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/dc11:identifier)'
        );
        
        if(!$id) {
            $id = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/dc10:identifier)'
            );
        }
        
        return $id;
    }
    
    public function getAuthors() {
        $authors = array();
        
        $list = $this->_xPath->query($this->_xPathPrefix.'//dc11:creator');
        
        if(!$list->length) {
            $list = $this->_xPath->query($this->_xPathPrefix.'//dc10:creator');
        }
        
        if(!$list->length) {
            $list = $this->_xPath->query($this->_xPathPrefix.'//dc11:publisher');
        }
        
        if(!$list->length) {
            $list = $this->_xPath->query($this->_xPathPrefix.'//dc10:publisher');
        }
        
        if($list->length) {
            foreach($list as $authorNode) {
                $author = new spur\feed\Author(
                    $authorNode->nodeValue
                );
                
                if($author->isValid()) {
                    $authors[] = $author;
                }
            }
        }
        
        return $authors;
    }
    
    public function getTitle() {
        $title = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/dc11:title)'
        );
        
        if(!$title) {
            $title = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/dc10:title)'
            );
        }
        
        return $title;
    }
    
    public function getDescription() {
        $description = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/dc11:description)'
        );
        
        if(!$description) {
            $description = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/dc10:description)'
            );
        }
        
        return $description;
    }
    
    public function getContent() {
        return $this->getDescription();
    }
    
    public function getCreationDate() {
        $date = null;
        
        $created = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/dc11:date)'
        );
        
        if(!$created) {
            $created = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/dc10:date)'
            );
        }
        
        if($created) {
            $date = core\time\Date::factory($created);
        }
        
        return $date;
    }
    
    public function getLastModifiedDate() {
        return $this->getCreationDate();
    }
    
    public function getCategories() {
        $list = $this->_xPath->query(
            $this->_xPathPrefix.'//dc11:subject'
        );
        
        if(!$list->length) {
            $list = $this->_xPath->query(
                $this->_xPathPrefix.'//dc10:subject'
            );
        }
        
        $categories = [];
        
        if($list->length) {
            foreach($list as $category) {
                $categories[] = new spur\feed\Category(
                    $category->nodeValue,
                    $category->getAttribute('domain'),
                    $category->nodeValue
                );
            }
        }
        
        return $categories;
    }
}