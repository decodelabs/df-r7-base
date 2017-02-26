<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\reader\rss;

use df;
use df\core;
use df\spur;

class Entry extends spur\feed\reader\Entry {

    protected $_xPathRss;
    protected $_xPathRdf;

    public function __construct(\DomElement $domElement, \DomXPath $xPath, $entryKey, $type=null) {
        parent::__construct($domElement, $xPath, $entryKey, $type);

        $this->_xPathRss = '//item['.($this->_entryKey+1).']';
        $this->_xPathRdf = '//rss:item['.($this->_entryKey+1).']';
    }

    protected function _getId() {
        $id = null;

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $id = $this->_xPath->evaluate('string('.$this->_xPathRss.'/guid)');
        }

        $id = $this->getFromPlugin(['dublinCore', 'atom'], 'id', $id);

        if(!$id) {
            if($link = $this->getPermalink()) {
                $id = $link;
            } else if($title = $this->getTitle()) {
                $id = $title;
            }
        }

        return $id;
    }

    protected function _getAuthors() {
        $authors = [];

        if($this->hasPlugin('dublinCore')) {
            foreach($this->getPlugin('dublinCore')->getAuthors() as $author) {
                $authors[] = new spur\feed\Author(
                    $author['name']
                );
            }
        }

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $list = $this->_xPath->query($this->_xPathRss.'//author');
        } else {
            $list = $this->_xPath->query($this->_xPathRdf.'//rss:author');
        }

        if($list->length) {
            foreach($list as $author) {
                if(preg_match("/^(.*@[^ ]*).*(\((.*)\))?$/", trim($author->nodeValue), $matches)) {
                    $author = new spur\feed\Author($matches[1]);

                    if(isset($matches[3])) {
                        $author->setName($matches[3]);
                    }

                    $authors[] = $author;
                }
            }
        }

        return $this->getFromPlugin('atom', 'authors', $authors);
    }

    protected function _getTitle() {
        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $title = $this->_xPath->evaluate('string('.$this->_xPathRss.'/title)');
        } else {
            $title = $this->_xPath->evaluate('string('.$this->_xPathRdf.'/rss:title)');
        }

        return $this->getFromPlugin(['dublinCore', 'atom'], 'title', $title);
    }

    protected function _getDescription() {
        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $description = $this->_xPath->evaluate(
                'string('.$this->_xPathRss.'/description)'
            );
        } else {
            $description = $this->_xPath->evaluate(
                'string('.$this->_xPathRdf.'/rss:description)'
            );
        }

        return $this->getFromPlugin(['dublinCore', 'atom'], 'description', $description);
    }

    protected function _getContent() {
        $content = $this->getFromPlugin('content', 'content');

        if(!$content) {
            $content = $this->getDescription();
        }

        return $this->getFromPlugin('atom', 'content', $content);
    }

    protected function _getLinks() {
        $links = [];

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $list = $this->_xPath->query($this->_xPathRss.'//link');
        } else {
            $list = $this->_xPath->query($this->_xPathRdf.'//rss:link');
        }

        if($list->length) {
            foreach($list as $link) {
                $links[] = $link->nodeValue;
            }
        }

        return $this->getFromPlugin('atom', 'links', $links);
    }

    protected function _getCommentCount() {
        return $this->getFromPlugin(['slash', 'thread'], 'commentCount', 0);
    }

    protected function _getCommentLink() {
        $commentLink = null;

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $commentLink = $this->_xPath->evaluate(
                'string('.$this->_xPathRss.'/comments)'
            );
        }

        return $this->getFromPlugin('atom', 'commentLink', $commentLink);
    }

    protected function _getCommentFeedLink() {
        return $this->getFromPlugin(['wellFormedWeb', 'atom'], 'commentFeedLink');
    }

    protected function _getCreationDate() {
        return $this->getLastModifiedDate();
    }

    protected function _getLastModifiedDate() {
        $date = null;

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $modified = $this->_xPath->evaluate(
                'string('.$this->_xPathRss.'/pubDate)'
            );

            if($modified) {
                try {
                    $date = core\time\Date::factory($modified);
                } catch(\Throwable $e) {
                    $parts = explode(',', $modified);

                    try {
                        $date = core\time\Date::factory(array_shift($parts).trim(array_shift($parts)));
                    } catch(\Throwable $e) {
                        $date = core\time\Date::now();
                    }
                }
            }
        }

        return $this->getFromPlugin(['dublinCore', 'atom'], 'lastModifiedDate', $date);
    }

    protected function _getEnclosure() {
        $enclosure = null;

        if($this->_type == spur\feed\ITypes::RSS_20) {
            $list = $this->_xPath->query($this->_xPathRss.'/enclosure');

            if($list->length) {
                $url = $list->item(0)->getAttribute('href');

                if(!$url) {
                    $url = $list->item(0)->getAttribute('url');
                }

                $enclosure = new spur\feed\Enclosure(
                    $url,
                    $list->item(0)->getAttribute('length'),
                    $list->item(0)->getAttribute('type')
                );
            }
        }

        return $this->getFromPlugin('atom', 'enclosure', $enclosure);
    }

    protected function _getCategories() {
        $categories = [];

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $list = $this->_xPath->evaluate($this->_xPathRss.'//category');
        } else {
            $list = $this->_xPath->evaluate($this->_xPathRss.'//rss:category');
        }

        if($list->length) {
            foreach($list as $category) {
                $categories[] = new spur\feed\Category(
                    $category->nodeValue,
                    $category->getAttribute('domain'),
                    $category->nodeValue
                );
            }
        }

        return $this->getFromPlugin(['dublinCore', 'atom'], 'categories', $categories);
    }
}