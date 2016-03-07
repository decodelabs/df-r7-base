<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed;

use df;
use df\core;
use df\spur;

class Image implements IImage {

    protected $_url;
    protected $_link;
    protected $_title;
    protected $_height;
    protected $_width;
    protected $_description;

    public function __construct($url) {
        $this->setUrl($url);
    }

    public function setUrl($url) {
        $this->_url = trim($url);
        return $this;
    }

    public function getUrl() {
        return $this->_url;
    }

    public function setLink($link) {
        $this->_link = trim($link);

        if(!strlen($this->_link)) {
            $this->_link = null;
        }

        return $this;
    }

    public function getLink() {
        return $this->_link;
    }

    public function hasLink() {
        return $this->_link !== null;
    }

    public function setTitle(string $title=null) {
        $this->_title = trim($title);

        if(!strlen($this->_title)) {
            $this->_title = null;
        }

        return $this;
    }

    public function getTitle() {
        return $this->_title;
    }

    public function hasTitle() {
        return $this->_title !== null;
    }

    public function setHeight($height) {
        $this->_height = trim($height);

        if(!strlen($this->_height)) {
            $this->_height = null;
        }

        return $this;
    }

    public function getHeight() {
        return $this->_height;
    }

    public function hasHeight() {
        return $this->_height !== null;
    }

    public function setWidth($width) {
        $this->_width = trim($width);

        if(!strlen($this->_width)) {
            $this->_width = null;
        }

        return $this;
    }

    public function getWidth() {
        return $this->_width;
    }

    public function hasWidth() {
        return $this->_width !== null;
    }

    public function setDescription($description) {
        $this->_description = trim(preg_replace('/[\s]{2,}/', ' ', $description));

        if(!strlen($this->_description)) {
            $this->_description = null;
        }

        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function hasDescription() {
        return $this->_description !== null;
    }
}