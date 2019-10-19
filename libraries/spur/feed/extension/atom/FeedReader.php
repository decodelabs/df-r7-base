<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\atom;

use df;
use df\core;
use df\spur;
use df\link;

class FeedReader implements spur\feed\IFeedReaderPlugin
{
    use spur\feed\TFeedReader;

    const XPATH_NAMESPACES = [];
    
    protected function _getXPathNamespaces()
    {
        if ($this->_type == spur\feed\ITypes::ATOM_10
        || $this->_type == spur\feed\ITypes::ATOM_03) {
            return [];
        }

        $prefix03 = $this->_domDocument->lookupPrefix(spur\feed\INamespaces::ATOM_03);
        $prefix10 = $this->_domDocument->lookupPrefix(spur\feed\INamespaces::ATOM_10);

        if ($this->_domDocument->isDefaultNamespace(spur\feed\INamespaces::ATOM_10) || $prefix10) {
            return ['atom' => spur\feed\INamespaces::ATOM_10];
        }

        if ($this->_domDocument->isDefaultNamespace(spur\feed\INamespaces::ATOM_03) || $prefix03) {
            return ['atom' => spur\feed\INamespaces::ATOM_03];
        }

        return ['atom' => spur\feed\INamespaces::ATOM_10];
    }

    public function getId(): ?string
    {
        $id = null;

        if ($this->_type != spur\feed\ITypes::RSS_10
        && $this->_type != spur\feed\ITypes::RSS_09) {
            $id = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:id)'
            );
        }

        if (!$id) {
            if ($link = $this->getSourceLink()) {
                $id = $link;
            } elseif ($title = $this->getTitle()) {
                $id = $title;
            } else {
                $id = null;
            }
        }

        return $id;
    }

    public function getAuthors()
    {
        $list = $this->_xPath->query('//atom:author');
        $authors = [];

        if ($list->length) {
            foreach ($list as $authorNode) {
                $author = new spur\feed\Author(
                    $authorNode->getElementsByTagName('email')->item(0)->nodeValue,
                    $authorNode->getElementsByTagName('name')->item(0)->nodeValue,
                    $authorNode->getElementsByTagName('uri')->item(0)->nodeValue
                );

                if ($author->isValid()) {
                    $authors[] = $author;
                }
            }
        }

        return $authors;
    }

    public function getTitle(): ?string
    {
        return $this->_xPath->evaluate('string('.$this->_xPathPrefix.'/atom:title)');
    }

    public function getDescription()
    {
        if ($this->_type == spur\feed\ITypes::ATOM_03) {
            $description = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:tagline)'
            );
        } else {
            $description = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:subtitle)'
            );
        }

        return $description;
    }

    public function getImage()
    {
        $image = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/atom:logo)'
        );

        if ($image) {
            $image = new spur\feed\Image($image);
        }

        return $image;
    }

    public function getCategories()
    {
        if ($this->_type == spur\feed\ITypes::ATOM_10) {
            $list = $this->_xPath->query(
                $this->_xPathPrefix.'/atom:category'
            );
        } else {
            $this->_xPath->registerNamespace('atom10', spur\feed\INamespaces::ATOM_10);
            $list = $this->_xPath->query(
                $this->_xPathPrefix.'/atom10:category'
            );
        }

        $categories = [];

        if ($list->length) {
            foreach ($list as $category) {
                $categories[] = new spur\feed\Category(
                    $category->nodeValue,
                    $category->getAttribute('domain'),
                    $category->nodeValue
                );
            }
        }

        return $categories;
    }

    public function getSourceLink()
    {
        $link = null;

        $list = $this->_xPath->query(
            $this->_xPathPrefix.'/atom:link[@rel="alternate"]/@href'.'|'.
            $this->_xPathPrefix.'/atom:link[not(@rel)]/@href'
        );

        if ($list->length) {
            $link = $this->_relativeToAbsoluteUrl(
                $list->item(0)->nodeValue
            );
        }

        return $link;
    }

    public function getFeedLink()
    {
        $link = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/atom:link[@rel="self"]/@href)'
        );

        if (strlen($link)) {
            $link = $this->_relativeToAbsoluteUrl($link);
        }

        return $link;
    }

    public function getBaseUrl()
    {
        return $this->_xPath->evaluate('string(//@xml:base[1])');
    }

    public function getLanguage()
    {
        $language = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/atom:lang)'
        );

        if (!$language) {
            $language = $this->_xPath->evaluate('string(//@xml:lang[1])');
        }

        return $language;
    }

    public function getCopyright()
    {
        if ($this->_type == spur\feed\ITypes::ATOM_03) {
            $copyright = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:copyright)'
            );
        } else {
            $copyright = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:rights)'
            );
        }

        return $copyright;
    }

    public function getCreationDate()
    {
        $date = null;

        if ($this->_type == spur\feed\ITypes::ATOM_03) {
            $created = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:created)'
            );
        } else {
            $created = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:published)'
            );
        }

        if ($created) {
            $date = core\time\Date::factory($created);
        }

        return $date;
    }

    public function getLastModifiedDate()
    {
        $date = $created = null;

        if ($this->_type == spur\feed\ITypes::ATOM_03) {
            $created = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:modified)'
            );
        } else {
            $created = $this->_xPath->evaluate(
                'string('.$this->_xPathPrefix.'/atom:updated)'
            );
        }

        if ($created) {
            $date = core\time\Date::factory($created);
        }

        return $date;
    }

    public function getGenerator()
    {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/atom:generator)'
        );
    }

    public function getHubs()
    {
        $hubs = [];
        $list = $this->_xPath->query(
            $this->_xPathPrefix.'//atom:link[@rel="hub"]/@href'
        );

        if ($list->length) {
            foreach ($list as $url) {
                $hubs[] = $this->_relativeToAbsoluteUrl($url);
            }
        }

        return $hubs;
    }

    protected function _relativeToAbsoluteUrl($urlString)
    {
        $url = link\http\Url::factory($urlString);

        if (!$url->hasDomain()) {
            if ($baseUrl = $this->getBaseUrl()) {
                $url = core\uri\Url::factory($baseUrl.$urlString);

                if (!$url->hasDomain()) {
                    return null;
                }
            }
        }

        return (string)$url;
    }
}
