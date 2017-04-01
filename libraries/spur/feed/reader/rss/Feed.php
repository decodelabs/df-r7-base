<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\reader\rss;

use df;
use df\core;
use df\spur;

class Feed extends spur\feed\reader\Feed {

    const DEFAULT_EXTENSIONS = [
        'dublinCore', 'content', 'atom', 'wellFormedWeb', 'slash', 'thread'
    ];

    public function getTypeName() {
        switch($this->_type) {
            case spur\feed\ITypes::RSS_09:
                return 'RSS 0.9';

            case spur\feed\ITypes::RSS_091:
                return 'RSS 0.91';

            case spur\feed\ITypes::RSS_091_NETSCAPE:
                return 'RSS 0.91n';

            case spur\feed\ITypes::RSS_091_USERLAND:
                return 'RSS 0.91u';

            case spur\feed\ITypes::RSS_092:
                return 'RSS 0.92';

            case spur\feed\ITypes::RSS_093:
                return 'RSS 0.93';

            case spur\feed\ITypes::RSS_094:
                return 'RSS 0.94';

            case spur\feed\ITypes::RSS_10:
                return 'RSS 1.0';

            case spur\feed\ITypes::RSS_20:
                return 'RSS 2.0';

            case spur\feed\ITypes::RSS:
            default:
                return 'RSS';
        }
    }

// Reader
    protected function _getXPathPrefix() {
        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            return '/rss/channel';
        } else {
            return '/rdf:RDF/rss:channel';
        }
    }

    protected function _getEntryXPathPrefix($entryKey) {
        if($this->_type == spur\feed\ITypes::RSS_10
        || $this->_type == spur\feed\ITypes::RSS_09) {
            return '//rss:item['.($entryKey+1).']';
        }

        return '//item['.($entryKey+1).']';
    }

    protected function _getXPathNamespaces() {
        switch($this->_type) {
            case spur\feed\ITypes::RSS_10:
                return [
                    'rdf' => spur\feed\INamespaces::RDF,
                    'rss' => spur\feed\INamespaces::RSS_10
                ];

            case spur\feed\ITypes::RSS_09:
                return [
                    'rdf' => spur\feed\INamespaces::RDF,
                    'rss' => spur\feed\INamespaces::RSS_09
                ];
        }
    }

    protected function _createEntry(\DomElement $domElement, $key) {
        return new Entry($domElement, $this->_xPath, $key, $this->_type);
    }


// Feed
    protected function _getId() {
        $id = null;

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $id = $this->_xPath->evaluate('string(/rss/channel/guid)');
        }

        $id = $this->getFromPlugin(['dublinCore', 'atom'], 'id', $id);

        if(!$id) {
            if($link = $this->getSourceLink()) {
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
                $authors[] = $author;
            }
        }

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $list = $this->_xPath->query('//author');
        } else {
            $list = $this->_xPath->query('//rss:author');
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
            $title = $this->_xPath->evaluate('string(/rss/channel/title)');
        } else {
            $title = $this->_xPath->evaluate('string(/rdf:RDF/rss:channel/rss:title)');
        }

        return $this->getFromPlugin(['dublinCore', 'atom'], 'title', $title);
    }

    protected function _getDescription() {
        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $description = $this->_xPath->evaluate(
                'string(/rss/channel/description)'
            );
        } else {
            $description = $this->_xPath->evaluate(
                'string(/rdf:RDF/rss:channel/rss:description)'
            );
        }

        return $this->getFromPlugin(['dublinCore', 'atom'], 'description', $description);
    }

    protected function _getImage() {
        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $list = $this->_xPath->query('/rss/channel/image');
            $prefix = '/rss/channel/image[1]';
        } else {
            $list = $this->_xPath->query('/rdf:RDF/rss:channel/rss:image');
            $prefix = '/rdf:RDF/rss:channel/rss:image[1]';
        }

        $image = null;

        if($list->length) {
            $image = new spur\feed\Image(
                $this->_xPath->evaluate('string('.$prefix.'/url)')
            );

            $image->setLink(
                    $this->_xPath->evaluate('string('.$prefix.'/link)')
                )
                ->setTitle(
                    $this->_xPath->evaluate('string('.$prefix.'/title)')
                )
                ->setHeight(
                    $this->_xPath->evaluate('string('.$prefix.'/height)')
                )
                ->setWidth(
                    $this->_xPath->evaluate('string('.$prefix.'/width)')
                )
                ->setDescription(
                    $this->_xPath->evaluate('string('.$prefix.'/description)')
                );
        }

        return $image;
    }

    protected function _getCategories() {
        $categories = [];

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $list = $this->_xPath->evaluate('/rss/channel//category');
        } else {
            $list = $this->_xPath->evaluate('/rdf:RDF/rss:channel//rss:category');
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

    protected function _getSourceLink() {
        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $sourceLink = $this->_xPath->evaluate('string(/rss/channel/link)');
        } else {
            $sourceLink = $this->_xPath->evaluate('string(/rdf:RDF/rss:channel/rss:link)');
        }

        return $this->getFromPlugin('atom', 'sourceLink', $sourceLink);
    }

    protected function _getFeedLink() {
        return $this->getFromPlugin('atom', 'feedLink');
    }

    protected function _getLanguage() {
        $language = null;

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $language = $this->_xPath->evaluate('string(/rss/channel/language)');
        }

        $language = $this->getFromPlugin(['dublinCore', 'atom'], 'language', $language);

        if(!$language) {
            $language = $this->_xPath->evaluate('string(//@xml:lang[1])');
        }

        return $language;
    }

    protected function _getCopyright() {
        $copyright = null;

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $copyright = $this->_xPath->evaluate('string(/rss/channel/copyright)');
        }

        return $this->getFromPlugin(['dublinCore', 'atom'], 'copyright', $copyright);
    }

    protected function _getCreationDate() {
        return $this->getLastModifiedDate();
    }

    protected function _getLastModifiedDate() {
        $date = null;

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $modified = $this->_xPath->evaluate('string(/rss/channel/pubDate)');

            if(!$modified) {
                $modified = $this->_xPath->evaluate('string(/rss/channel/lastBuildDate)');
            }

            $date = core\time\Date::normalize($modified);
        }

        return $this->getFromPlugin(['dublinCore', 'atom'], 'lastModifiedDate', $date);
    }

    protected function _getGenerator() {
        $generator = null;

        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $generator = $this->_xPath->evaluate('string(/rss/channel/generator)');
        }

        if(!$generator) {
            if($this->_type != spur\feed\ITypes::RSS_10
            && $this->_type != spur\feed\ITypes::RSS_09) {
                $generator = $this->_xPath->evaluate('string(/rss/channel/atom:generator)');
            } else {
                $generator = $this->_xPath->evaluate('string(/rdf:RDF/rss:channel/atom:generator)');
            }
        }

        return $this->getFromPlugin('atom', 'generator', $generator);
    }

    protected function _getHubs() {
        return $this->getFromPlugin('atom', 'hubs', []);
    }

    protected function _getEntryNodeList() {
        if($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            return $this->_xPath->evaluate('//item');
        } else {
            return $this->_xPath->evaluate('//rss:item');
        }
    }
}