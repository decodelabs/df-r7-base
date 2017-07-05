<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\atom;

use df;
use df\core;
use df\spur;

class EntryReader implements spur\feed\IEntryReaderPlugin {

    use spur\feed\TEntryReader;

    public function getId(): ?string {
        $id = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/atom:id)'
        );

        if(!$id) {
            if($link = $this->getPermalink()) {
                $id = $link;
            } else if($title = $this->getTitle()) {
                $id = $title;
            }
        }

        return $id;
    }

    public function getAuthors() {
        $authors = [];

        $list = $this->_xPath->query(
            $this->_xPathPrefix.'//atom:author'
        );

        if(!$list->length) {
            $list = $this->_xPath->query(
                '//atom:author'
            );
        }

        if($list->length) {
            foreach($list as $authorNode) {
                $author = new spur\feed\Author(
                    @$authorNode->getElementsByTagName('email')->item(0)->nodeValue,
                    @$authorNode->getElementsByTagName('name')->item(0)->nodeValue,
                    @$authorNode->getElementsByTagName('uri')->item(0)->nodeValue
                );

                if($author->isValid()) {
                    $authors[] = $author;
                }
            }
        }

        return $authors;
    }

    public function getTitle(): ?string {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/atom:title)'
        );
    }

    public function getDescription() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/atom:summary)'
        );
    }

    public function getContent() {
        $content = $this->_xPath->query(
            $this->_xPathPrefix.'/atom:content'
        );

        if($content->length) {
            $content = $content->item(0);

            switch($content->getAttribute('type')) {
                case '':
                case 'text':
                case 'text/plain':
                case 'html':
                case 'text/html':
                    $content = $content->nodeValue;
                    break;

                case 'xhtml':
                    $this->_xPath->registerNamespace(
                        'xhtml', 'http://www.w3.org/1999/xhtml'
                    );

                    $xhtml = $this->_xPath->query(
                            $this->_xPathPrefix.'/atom:content/xhtml:div'
                        )
                        ->item(0);

                    $xDoc = new \DomDocument('1.0', $this->getEncoding());
                    $xDoc->appendChild($xDoc->importNode($xhtml, true));

                    $content = $this->_normalizeXhtml(
                        $xDoc->saveXml(),
                        $xDoc->lookupPrefix('http://www.w3.org/1999/xhtml')
                    );

                    break;
            }
        } else {
            $content = $this->getDescription();
        }

        return $content;
    }

    protected function _normalizeXhtml($xhtml, $prefix) {
        if($prefix) {
            $prefix .= ':';
        }

        $regexes = [
            '/<\?xml[^<]*>[^<]*<'.$prefix.'div[^<]*/',
            '/<\/'.$prefix.'div>\s*$/'
        ];

        $xhtml = preg_replace($regexes, '', $xhtml);

        if($prefix) {
            $xhtml = preg_replace(
                '/(<[\/]?)'.$prefix.'([a-zA-Z]+)/', '$1$2', $xhtml
            );
        }

        return $xhtml;
    }

    public function getLinks() {
        $links = [];

        $list = $this->_xPath->query(
            $this->_xPathPrefix.'//atom:link[@rel="alternative"]/@href'.'|'.
            $this->_xPathPrefix.'//atom:link[not(@rel)]/@href'
        );

        if($list->length) {
            foreach($list as $link) {
                $links[] = $this->_relativeToAbsoluteUrl($link->value);
            }
        }

        return $links;
    }

    public function getBaseUrl() {
        $baseUrl = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/@xml:base[1])'
        );

        if(!$baseUrl) {
            $baseUrl = $this->_xPath->evaluate(
                'string(//@xml:base[1])'
            );
        }

        return $baseUrl;
    }

    public function getCommentLink() {
        $link = null;

        $list = $this->_xPath->query(
            $this->_xPathPrefix.'//atom:link[@rel="replies" and @type="text/html"]/@href'
        );

        if($list->length) {
            $link = $this->_relativeToAbsoluteUrl($list->item(0)->value);
        }

        return $link;
    }

    public function getCommentFeedLink() {
        $link = null;

        $list = $this->_xPath->query(
            $this->_xPathPrefix.'//atom:link[@rel="replies" and @type="application/atom+xml"]/@href'
        );

        if(!$list->length) {
            $list = $this->_xPath->query(
                $this->_xPathPrefix.'//atom:link[@rel="replies" and @type="application/rss+xml"]/@href'
            );
        }

        if($list->length) {
            $link = $this->_relativeToAbsoluteUrl($list->item(0)->value);
        }

        return $link;
    }

    public function getCreationDate() {
        $date = null;

        if($this->_type == spur\feed\ITypes::ATOM_03) {
            $created = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:created)'
            );
        } else {
            $created = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:published)'
            );
        }

        if($created) {
            $date = core\time\Date::factory($created);
        }

        return $date;
    }

    public function getLastModifiedDate() {
        $date = null;

        if($this->_type == spur\feed\ITypes::ATOM_03) {
            $modified = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:modified)'
            );
        } else {
            $created = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:updated)'
            );
        }

        if($created) {
            $date = core\time\Date::factory($created);
        }

        return $date;
    }

    public function getEnclosure() {
        $enclosure = null;

        $list = $this->_xPath->query(
            $this->_xPathPrefix.'/atom:link[@rel="enclosure"]'
        );

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

        return $enclosure;
    }

    public function getCategories() {
        if($this->_type == spur\feed\ITypes::ATOM_10) {
            $list = $this->_xPath->query(
                $this->_xPathPrefix.'//atom:category'
            );
        } else {
            $this->_xPath->registerNamespace(
                'atom10', spur\feed\INamespaces::ATOM_10
            );

            $list = $this->_xPath->query(
                $this->_xPathPrefix.'//atom10:category'
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


    // TODO: public function getSource() {}

    protected function _getXPathNamespaces() {
        $prefix03 = $this->_domDocument->lookupPrefix(spur\feed\INamespaces::ATOM_03);
        $prefix10 = $this->_domDocument->lookupPrefix(spur\feed\INamespaces::ATOM_10);

        if($this->_domDocument->isDefaultNamespace(spur\feed\INamespaces::ATOM_10) || $prefix10) {
            return ['atom' => spur\feed\INamespaces::ATOM_10];
        }

        if($this->_domDocument->isDefaultNamespace(spur\feed\INamespaces::ATOM_03) || $prefix03) {
            return ['atom' => spur\feed\INamespaces::ATOM_03];
        }

        return ['atom' => spur\feed\INamespaces::ATOM_10];
    }

    protected function _relativeToAbsoluteUrl($urlString) {
        $url = core\uri\Url::factory($urlString);

        if(!$url->hasDomain()) {
            if($baseUrl = $this->getBaseUrl()) {
                $url = core\uri\Url::factory($baseUrl.$urlString);

                if(!$url->hasDomain()) {
                    return null;
                }
            }
        }

        return (string)$url;
    }
}
