<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\reader;

use df;
use df\core;
use df\spur;

abstract class Feed implements spur\feed\IFeedReader
{
    use spur\feed\TFeedReader;
    use spur\feed\TAuthorProvider;
    use spur\feed\TStoreProvider;
    use spur\feed\TPluginProvider;

    const XPATH_NAMESPACES = [];
    const DEFAULT_EXTENSIONS = [];

    protected $_feedLink = null;
    protected $_extensions = [];
    protected $_entryPlugins = [];
    protected $_entries = [];

    public static function fromFile($path)
    {
        if (!is_file($path)) {
            throw new spur\feed\RuntimeException(
                'Feed file could not be found'
            );
        }

        return self::fromString(file_get_contents($path));
    }

    public static function fromString(?string $string)
    {
        $domDocument = self::_loadDomDocument($string);
        $type = self::detectFeedType($domDocument);

        if (substr($type, 0, 3) == 'rss') {
            $output = new namespace\rss\Feed($domDocument, null, $type);
        } elseif (substr($type, -5) == 'entry') {
            $output = new namespace\atom\Entry($domDocument->documentElement, 0, spur\feed\ITypes::ATOM_10);
        } elseif (substr($type, 0, 4) == 'atom') {
            $output = new namespace\atom\Feed($domDocument, null, $type);
        } else {
            throw new spur\feed\UnexpectedValueException(
                'The feed is not a recognised type'
            );
        }

        return $output;
    }

    protected static function _loadDomDocument($string)
    {
        try {
            $domDocument = new \DOMDocument();
            $domDocument->loadXml($string);
        } catch (\Throwable $e) {
            throw new spur\feed\UnexpectedValueException(
                'Could not load feed xml document', 0, $e
            );
        }

        return $domDocument;
    }

    public static function detectFeedType($domDocument, $specOnly=false)
    {
        if ($domDocument instanceof spur\feed\IReader) {
            $domDocument = $domDocument->getDomDocument();
        } elseif (is_string($domDocument)) {
            $domDocument = self::_loadDomDocument($domDocument);
        }

        if (!$domDocument instanceof \DOMDocument) {
            throw new spur\feed\UnexpectedValueException(
                'Cannot detect feed type - invalid xml document'
            );
        }

        $xPath = new \DOMXPath($domDocument);

        if ($xPath->query('/rss')->length) {
            switch ($xPath->evaluate('string(/rss/@version)')) {
                case '2.0':  return spur\feed\ITypes::RSS_20;
                case '0.94': return spur\feed\ITypes::RSS_094;
                case '0.93': return spur\feed\ITypes::RSS_093;
                case '0.92': return spur\feed\ITypes::RSS_092;
                case '0.91': return spur\feed\ITypes::RSS_091;
                default:     return spur\feed\ITypes::RSS;
            }
        }

        $xPath->registerNamespace('rdf', spur\feed\INamespaces::RDF);

        if ($xPath->query('/rdf:RDF')->length) {
            $xPath->registerNamespace('rss', spur\feed\INamespaces::RSS_10);

            if ($xPath->query('/rdf:RDF/rss:channel')->length
            || $xPath->query('/rdf:RDF/rss:image')->length
            || $xPath->query('/rdf:RDF/rss:item')->length
            || $xPath->query('/rdf:RDF/rss:textinput')->length) {
                return spur\feed\ITypes::RSS_10;
            }


            $xPath->registerNamespace('rss', spur\feed\INamespaces::RSS_09);

            if ($xPath->query('/rdf:RDF/rss:channel')->length
            || $xPath->query('/rdf:RDF/rss:image')->length
            || $xPath->query('/rdf:RDF/rss:item')->length
            || $xPath->query('/rdf:RDF/rss:textinput')->length) {
                return spur\feed\ITypes::RSS_09;
            }
        }

        $type = spur\feed\ITypes::ATOM;
        $xPath->registerNamespace('atom', spur\feed\INamespaces::ATOM_10);

        if ($xPath->query('//atom:feed')->length) {
            return spur\feed\ITypes::ATOM_10;
        }

        if ($xPath->query('//atom:entry')->length) {
            if ($specOnly) {
                return spur\feed\ITypes::ATOM_10;
            }

            return spur\feed\ITypes::ATOM_10_ENTRY;
        }


        $xPath->registerNamespace('atom', spur\feed\INamespaces::ATOM_03);

        if ($xPath->query('//atom:feed')->length) {
            return spur\feed\ITypes::ATOM_03;
        }

        return spur\feed\ITypes::ANY;
    }


    protected function _init()
    {
        $this->_xPathPrefix = $this->_getXPathPrefix();

        foreach ($this->_getEntryNodeList() as $i => $entry) {
            $this->_entries[$i] = $entry;
        }

        foreach (static::DEFAULT_EXTENSIONS as $ext) {
            $this->loadExtension($ext);
        }
    }

    abstract protected function _getXPathPrefix();


    // Feed
    public function getId(): ?string
    {
        return $this->_getDefaultValue('id');
    }

    public function getAuthor($index=0)
    {
        $authors = $this->getAuthors();

        if (isset($authors[$index])) {
            return $authors[$index];
        }

        return null;
    }

    public function getAuthors()
    {
        return $this->_getDefaultValue('authors');
    }

    public function getTitle(): ?string
    {
        return $this->_getDefaultValue('title');
    }

    public function getDescription()
    {
        return $this->_getDefaultValue('description');
    }

    public function getImage()
    {
        return $this->_getDefaultValue('image');
    }

    public function getCategories()
    {
        return $this->_getDefaultValue('categories');
    }

    public function getSourceLink()
    {
        return $this->_getDefaultValue('sourceLink');
    }

    public function setFeedLink($link)
    {
        $this->_feedLink = (string)$link;
        return $this;
    }

    public function getFeedLink()
    {
        if ($link = $this->_getDefaultValue('feedLink')) {
            return $link;
        }

        return $this->_feedLink;
    }

    public function getLanguage()
    {
        return $this->_getDefaultValue('language');
    }

    public function getCopyright()
    {
        return $this->_getDefaultValue('copyright');
    }

    public function getCreationDate()
    {
        return $this->_getDefaultValue('creationDate');
    }

    public function getLastModifiedDate()
    {
        return $this->_getDefaultValue('lastModifiedDate');
    }

    public function getGenerator()
    {
        return $this->_getDefaultValue('generator');
    }

    public function getHubs()
    {
        return $this->_getDefaultValue('hubs');
    }

    public function getEncoding()
    {
        if ($encoding = $this->_getDefaultValue('encoding')) {
            return $encoding;
        }

        $output = $this->getDomDocument()->encoding;

        if (empty($output)) {
            $output = 'UTF-8';
        }

        return $output;
    }


    // Entries
    public function getEntry($index=0)
    {
        $entry = $this->_createEntry($this->_entries[$index], $index);

        foreach ($this->_entryPlugins as $name => $class) {
            $plugin = new $class(
                $entry->getDomElement(),
                $entry->getXPath(),
                $entry->getEntryKey(),
                $entry->getType()
            );

            if ($plugin instanceof spur\feed\IEntryReaderPlugin) {
                $plugin->setXPathPrefix($this->_getEntryXPathPrefix($entry->getEntryKey()));
                $entry->addPlugin($name, $plugin);
            }
        }

        return $entry;
    }

    abstract protected function _getEntryXPathPrefix($entryKey);

    public function getEntries()
    {
        $output = [];

        foreach ($this as $entry) {
            $output[] = $entry;
        }

        return $output;
    }

    abstract protected function _createEntry(\DomElement $domElement, $key);
    abstract protected function _getEntryNodeList();

    // Extensions
    public function loadExtension($name)
    {
        $name = lcfirst($name);
        $this->_extensions[$name] = true;
        $class = 'df\\spur\\feed\\extension\\'.$name.'\\EntryReader';

        if (class_exists($class)) {
            $this->_entryPlugins[$name] = $class;
        }

        $class = 'df\\spur\\feed\\extension\\'.$name.'\\FeedReader';

        if (!class_exists($class)) {
            return $this;
        }

        $plugin = new $class(
            $this->_domDocument,
            $this->_xPath,
            $this->_type
        );

        if ($plugin instanceof spur\feed\IFeedReaderPlugin) {
            $plugin->setXPathPrefix($this->getXPathPrefix());
            $this->_plugins[$name] = $plugin;
        }

        return $this;
    }

    public function hasExtension($name)
    {
        $name = lcfirst($name);
        return isset($this->_extensions[$name]);
    }



    // Iterator / countable
    public function count()
    {
        return count($this->_entries);
    }


    public function key()
    {
        return key($this->_entries);
    }

    public function next()
    {
        return next($this->_entries);
    }

    public function rewind()
    {
        return reset($this->_entries);
    }

    public function current()
    {
        return $this->getEntry($this->key());
    }

    public function valid()
    {
        $key = $this->key();
        return $key !== null && $key < $this->count();
    }
}
