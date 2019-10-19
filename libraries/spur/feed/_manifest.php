<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed;

use df;
use df\core;
use df\spur;

// Exceptions
interface IException
{
}
class RuntimeException extends \RuntimeException implements IException
{
}
class UnexpectedValueException extends \UnexpectedValueException implements IException
{
}


// Interfaces
interface INamespaces
{
    const ATOM_03 = 'http://purl.org/atom/ns#';
    const ATOM_10 = 'http://www.w3.org/2005/Atom';
    const RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    const RSS_09 = 'http://my.netscape.com/rdf/simple/0.9/';
    const RSS_10 = 'http://purl.org/rss/1.0/';
}

interface ITypes
{
    const ANY = 'any';
    const ATOM_03 = 'atom-03';
    const ATOM_10 = 'atom-10';
    const ATOM_10_ENTRY = 'atom-10-entry';
    const ATOM = 'atom';
    const RSS_09 = 'rss-090';
    const RSS_091 = 'rss-091';
    const RSS_091_NETSCAPE = 'rss-091n';
    const RSS_091_USERLAND = 'rss-091u';
    const RSS_092 = 'rss-092';
    const RSS_093 = 'rss-093';
    const RSS_094 = 'rss-094';
    const RSS_10 = 'rss-10';
    const RSS_20 = 'rss-20';
    const RSS = 'rss';
}


interface IAuthorProvider
{
    public function getAuthor($index=0);
    public function getAuthors();
    public function getAuthorNameList();
    public function getAuthorEmailList();
}

trait TAuthorProvider
{
    public function getAuthorNameList()
    {
        $output = [];

        foreach ($this->getAuthors() as $author) {
            if ($author->hasName()) {
                $output[] = $author->getName();
            }
        }

        return $output;
    }

    public function getAuthorEmailList()
    {
        $output = [];

        foreach ($this->getAuthors() as $author) {
            if ($author->hasEmail()) {
                $output[] = $author->getEmail();
            }
        }

        return $output;
    }
}

interface IDescriptionProvider
{
    public function getTitle(): ?string;
    public function getDescription();
}

interface ICategorized
{
    public function getCategories();
}

interface ITimestamped
{
    public function getCreationDate();
    public function getLastModifiedDate();
}


// Store
trait TStoreProvider
{
    protected $_store = [];

    protected function _setStore($name, $value)
    {
        if (is_string($value)) {
            $value = trim(preg_replace('/[\s]{2,}/', ' ', $value));

            if (!strlen($value)) {
                $value = null;
            }
        }

        $this->_store[$name] = $value;

        return $this;
    }

    protected function _getStore($name)
    {
        if (isset($this->_store[$name])) {
            return $this->_store[$name];
        }

        return null;
    }

    protected function _hasStore($name)
    {
        return array_key_exists($name, $this->_store);
    }

    protected function _getDefaultValue($storeId)
    {
        if (!$this->_hasStore($storeId)) {
            $method = 'get'.ucfirst($storeId);

            if (method_exists($this, '_'.$method)) {
                $method = '_'.$method;
                $value = $this->$method();
            } else {
                $value = $this->__call($method);
            }

            $this->_setStore($storeId, $value);
        }

        return $this->_getStore($storeId);
    }

    public function __call($method, $args=[])
    {
        $storeId = null;

        if (substr($method, 0, 3) == 'get') {
            $storeId = lcfirst(substr($method, 3));

            if ($this->_hasStore($storeId)) {
                return $this->_getStore($storeId);
            }
        }

        foreach ($this->_plugins as $plugin) {
            if (method_exists($plugin, $method)) {
                $output = $plugin->{$method}(...$args);

                if ($storeId) {
                    $this->_setStore($storeId, $output);
                }

                return $output;
            }
        }

        return null;
    }
}


// Plugins
interface IPlugin
{
}

interface IFeedReaderPlugin extends IPlugin
{
    public function setXPathPrefix($prefix);
}

interface IEntryReaderPlugin extends IPlugin
{
}

interface IPluginProvider
{
    public function hasPlugin($name);
    public function getPlugin($name);
    public function getFromPlugin($plugins, $var, $inValue=null);
}

trait TPluginProvider
{
    protected $_plugins = [];

    public function hasPlugin($name)
    {
        $name = lcfirst($name);
        return isset($this->_plugins[$name]);
    }

    public function getPlugin($name)
    {
        $name = lcfirst($name);

        if (isset($this->_plugins[$name])) {
            return $this->_plugins[$name];
        }

        return null;
    }

    public function getFromPlugin($plugins, $var, $inValue=null)
    {
        $isArray = false;

        if (is_array($inValue)) {
            $isArray = true;

            if (!empty($inValue)) {
                return $inValue;
            }
        } else {
            if (is_string($inValue) && !strlen($inValue)) {
                $inValue = null;
            }

            if ($inValue !== null) {
                return $inValue;
            }
        }

        if (!is_array($plugins)) {
            $plugins = [$plugins];
        }

        $method = 'get'.ucfirst($var);

        foreach ($plugins as $pluginName) {
            if (!$this->hasPlugin($pluginName)) {
                continue;
            }

            $plugin = $this->getPlugin($pluginName);

            if (method_exists($plugin, $method)
            && ($val = $plugin->$method())) {
                if ($isArray && empty($val)) {
                    continue;
                }

                return $val;
            }
        }

        return $inValue;
    }
}


// Reader
interface IReader
{
    public function getDomDocument();
    public function getXPath();
}


trait TReader
{
    protected $_type;
    protected $_domDocument;
    protected $_xPath;
    protected $_xPathPrefix;

    protected function _init()
    {
    }

    public function getDomDocument()
    {
        return $this->_domDocument;
    }

    public function getXPath()
    {
        return $this->_xPath;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setXPathPrefix($prefix)
    {
        $this->_xPathPrefix = $prefix;
        return $this;
    }

    public function getXPathPrefix()
    {
        return $this->_xPathPrefix;
    }

    protected function _getXPathNamespaces()
    {
        return static::XPATH_NAMESPACES;
    }
}


interface IFeed extends
    \Iterator,
    \Countable,
    IAuthorProvider,
    IDescriptionProvider,
    ICategorized,
    ITimestamped
{
    public function getId(): ?string;
    public function getType();
    public function getTypeName();
    public function getImage();

    public function getSourceLink();
    public function getFeedLink();
    public function getLanguage();
    public function getCopyright();

    public function getGenerator();
    public function getHubs();
    public function getEncoding();

    public function getEntry($index=0);
    public function getEntries();
}

interface IFeedReader extends IFeed, IReader, IPluginProvider
{
    public function setXPathPrefix($prefix);
    public function getXPathPrefix();

    public function loadExtension($name);
    public function hasExtension($name);
}

trait TFeedReader
{
    use TReader;

    public function __construct(\DomDocument $domDocument, \DomXPath $xPath=null, $type=null)
    {
        $this->_domDocument = $domDocument;

        if ($xPath === null) {
            $xPath = new \DOMXPath($this->_domDocument);
        }

        $this->_xPath = $xPath;

        if ($type === null) {
            $type = spur\feed\reader\Feed::detectFeedType($domDocument);
        }

        $this->_type = $type;

        $ns = $this->_getXPathNamespaces();

        if (is_array($ns) && !empty($ns)) {
            foreach ($ns as $alias => $namespace) {
                $this->_xPath->registerNamespace($alias, $namespace);
            }
        }

        $this->_init();
    }
}

interface IEntry extends
    IAuthorProvider,
    IDescriptionProvider,
    ICategorized,
    ITimestamped
{
    public function getId(): ?string;
    public function getContent();

    public function getPermalink();
    public function getLink($index=0);
    public function getLinks();

    public function getCommentCount();
    public function getCommentLink();
    public function getCommentFeedLink();

    public function getEnclosure();
}

interface IEntryReader extends IEntry, IReader, IPluginProvider
{
    public function getDomElement();
    public function getEntryKey();
    public function addPlugin($extension, spur\feed\IEntryReaderPlugin $plugin);
}

trait TEntryReader
{
    use TReader;

    protected $_entryKey;
    protected $_domElement;

    public function __construct(\DomElement $domElement, \DomXPath $xPath, $entryKey, $type=null)
    {
        $this->_entryKey = $entryKey;
        $this->_domElement = $domElement;
        $this->_domDocument = $this->_domElement->ownerDocument;
        $this->_xPath = $xPath;

        if ($type === null) {
            $type = spur\feed\reader\Feed::detectFeedType($this->_domDocument);
        }

        $this->_type = $type;

        $ns = $this->_getXPathNamespaces();

        if (is_array($ns) && count($ns)) {
            foreach ($ns as $alias => $namespace) {
                $this->_xPath->registerNamespace($alias, $namespace);
            }
        }

        $this->_init();
    }

    public function getDomElement()
    {
        return $this->_domElement;
    }

    public function getEntryKey()
    {
        return $this->_entryKey;
    }

    public function getPermalink()
    {
        if (method_exists($this, 'getLink')) {
            return $this->getLink(0);
        }

        if (method_exists($this, 'getLinks')) {
            if (count($links = $this->getLinks())) {
                return $links[0];
            }
        }

        return null;
    }
}



interface IEnclosure
{
    public function setUrl($url);
    public function getUrl();
    public function setLength($length);
    public function getLength();
    public function setType($type);
    public function getType();
}


interface ICategory
{
    public function setTerm($term);
    public function getTerm();
    public function hasTerm();

    public function setScheme($scheme);
    public function getScheme();
    public function hasScheme();

    public function setLabel($label);
    public function getLabel();
    public function hasLabel();

    public function setChildren(array $children);
    public function getChildren();
    public function hasChildren();
}


interface IAuthor
{
    public function setName($name);
    public function getName();
    public function hasName();

    public function setEmail($email);
    public function getEmail();
    public function hasEmail();

    public function setUrl($url);
    public function getUrl();
    public function hasUrl();

    public function isValid(): bool;
}

interface IImage
{
    public function setUrl($url);
    public function getUrl();
    public function setLink($link);
    public function getLink();
    public function hasLink();
    public function setTitle(?string $title);
    public function getTitle(): ?string;
    public function hasTitle();
    public function setHeight($height);
    public function getHeight();
    public function hasHeight();
    public function setWidth($width);
    public function getWidth();
    public function hasWidth();
    public function setDescription($description);
    public function getDescription();
    public function hasDescription();
}
