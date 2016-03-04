<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\reader;

use df;
use df\core;
use df\spur;

class Entry implements spur\feed\IEntryReader {

    use spur\feed\TAuthorProvider;
    use spur\feed\TEntryReader;
    use spur\feed\TStoreProvider;
    use spur\feed\TPluginProvider;

    const XPATH_NAMESPACES = [];

// Entry
    public function getId() {
        return $this->_getDefaultValue('id');
    }

    public function getAuthor($index=0) {
        $authors = $this->getAuthors();

        if(isset($authors[$index])) {
            return $authors[$index];
        }

        return null;
    }

    public function getAuthors() {
        return $this->_getDefaultValue('authors');
    }

    public function getTitle() {
        return $this->_getDefaultValue('title');
    }

    public function getDescription() {
        return $this->_getDefaultValue('description');
    }

    public function getContent() {
        return $this->_getDefaultValue('content');
    }

    public function getLink($index=0) {
        $urls = $this->getLinks();

        if(isset($urls[$index])) {
            return $urls[$index];
        }

        return null;
    }

    public function getLinks() {
        return $this->_getDefaultValue('links');
    }

    public function getPermalink() {
        if($link = $this->_getDefaultValue('permalink')) {
            return $link;
        }

        return $this->getLink(0);
    }

    public function getCommentCount() {
        return $this->_getDefaultValue('commentCount');
    }

    public function getCommentLink() {
        return $this->_getDefaultValue('commentLink');
    }

    public function getCommentFeedLink() {
        return $this->_getDefaultValue('commentFeedLink');
    }

    public function getCreationDate() {
        return $this->_getDefaultValue('creationDate');
    }

    public function getLastModifiedDate() {
        return $this->_getDefaultValue('lastModifiedDate');
    }

    public function getEnclosure() {
        return $this->_getDefaultValue('enclosure');
    }

    public function getCategories() {
        return $this->_getDefaultValue('categories');
    }

// Plugins
    public function addPlugin($extension, spur\feed\IEntryReaderPlugin $plugin) {
        $this->_plugins[$extension] = $plugin;
        return $this;
    }
}