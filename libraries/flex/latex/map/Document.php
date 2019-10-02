<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\map;

use df;
use df\core;
use df\flex;
use df\iris;

class Document extends iris\map\Node implements flex\latex\IDocument
{
    use flex\latex\TContainerNode;

    protected $_documentClass;
    protected $_options = [];
    protected $_packages = [];

    protected $_title;
    protected $_author;
    protected $_date;


    // Class
    public function setDocumentClass($class)
    {
        $this->_documentClass = $class;
        return $this;
    }

    public function getDocumentClass()
    {
        return $this->_documentClass;
    }


    // Options
    public function setOptions(array $options)
    {
        $this->_options = [];
        return $this->addOptions($options);
    }

    public function addOptions(array $options)
    {
        $this->_options = array_unique(array_merge($this->_options, $options));
        return $this;
    }

    public function addOption($option)
    {
        if (!in_array($option, $this->_options)) {
            $this->_options[] = $option;
        }

        return $this;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function clearOptions()
    {
        $this->_options = [];
        return $this;
    }

    // Packages
    public function addPackage($name, array $options=[])
    {
        $this->_packages[$name] = $options;
        return $this;
    }

    public function hasPackage($name)
    {
        return isset($this->_packages[$name]);
    }

    public function getPackages()
    {
        return $this->_packages;
    }


    // Top matter
    public function setTitle(?string $title)
    {
        $this->_title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->_title;
    }

    public function setAuthor($author)
    {
        $this->_author = $author;
        return $this;
    }

    public function getAuthor()
    {
        return $this->_author;
    }

    public function setDate($date)
    {
        $this->_date = core\time\Date::normalize($date);
        return $this;
    }

    public function getDate()
    {
        return $this->_date;
    }


    // References
    public function getBibliography()
    {
        foreach ($this->_collection as $child) {
            if ($child instanceof flex\latex\map\Bibliography) {
                return $child;
            }
        }
    }
}
