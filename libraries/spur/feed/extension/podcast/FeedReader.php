<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\podcast;

use df;
use df\core;
use df\spur;

class FeedReader implements spur\feed\IFeedReaderPlugin {

    use spur\feed\TFeedReader;

    const XPATH_NAMESPACES = [
        'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd'
    ];

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

    public function getCategories() {
        $list = $this->_xPath->query(
            $this->_xPathPrefix.'/itunes:category'
        );

        $categories = [];

        if($list->length) {
            foreach($list as $catNode) {
                $category = new spur\feed\Category($catNode->getAttribute('text'));

                if($catNode->childNodes->length) {
                    $children = [];

                    foreach($catNode->childNodes as $child) {
                        if(!$child instanceof \DomText) {
                            $children[] = new spur\feed\Category(
                                $child->getAttribute('text')
                            );
                        }
                    }

                    $category->setChildren($children);
                }

                $categories[] = $category;
            }
        }

        return $categories;
    }

    public function getExplicit() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:explicit)'
        );
    }

    public function getImage() {
        $image = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:image)'
        );

        if($image) {
            $image = new spur\feed\Image($image);
        }

        return $image;
    }

    public function getKeywords() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:keywords)'
        );
    }

    public function getNewFeedUrl() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:new-feed-url)'
        );
    }

    public function getOwner() {
        $email = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:owner/itunes:email)'
        );

        $name = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/itunes:owner/itunes:name)'
        );

        if(!$email && !$name) {
            return null;
        }

        return new spur\feed\Author($name, $email);
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